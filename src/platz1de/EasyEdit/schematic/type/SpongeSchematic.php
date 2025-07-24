<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\TileConvertor;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\schematic\nbt\AbstractByteArrayTag;
use platz1de\EasyEdit\schematic\nbt\AbstractListTag;
use platz1de\EasyEdit\schematic\nbt\AbstractNBT;
use platz1de\EasyEdit\schematic\nbt\AbstractNBTSerializer;
use platz1de\EasyEdit\schematic\nbt\BigEndianAbstractNBTSerializer;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\InternetException;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\World;
use Throwable;
use UnexpectedValueException;

class SpongeSchematic extends SchematicType
{
	/**
	 * Version 1: Initial version
	 * Version 2: Entity support, needs DataVersion field
	 * Version 3: 3D biomes, new Palette location
	 */
	public const FORMAT_VERSION = "Version";
	public const METADATA = "Metadata";
	public const BLOCK_DATA_LEGACY = "BlockData";
	public const PALETTE = "Palette";
	public const DATA = "Data";
	public const DATA_BLOCKS = "Blocks";
	public const BLOCK_ENTITY_DATA = "BlockEntities";
	public const UNUSED_DATA_VERSION = "DataVersion";
	public const ENTITY_POSITION = "Pos"; //also used for tile entities
	public const ENTITY_ID = "Id"; //also used for tile entities
	public const ENTITY_EXTRA_DATA = "Data"; //also used for tile entities
	public const ROOT_SCHEMATIC = "Schematic";

	/**
	 * @param CompoundTag               $nbt
	 * @param DynamicBlockListSelection $target
	 * @throws CancelException
	 */
	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		if (!BlockStateConvertor::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		//Sponge v3 wraps the schematic in a compound tag
		if ($nbt->getCompoundTag(self::ROOT_SCHEMATIC) !== null) {
			$nbt = $nbt->getCompoundTag(self::ROOT_SCHEMATIC);
		}
		$version = $nbt->getInt(self::FORMAT_VERSION, 1);
		$offset = BlockOffsetVector::zero();
		$metaData = $nbt->getCompoundTag(self::METADATA);
		if ($metaData !== null) {
			$offset = new BlockOffsetVector($metaData->getInt(McEditSchematic::OFFSET_X, 0), $metaData->getInt(McEditSchematic::OFFSET_Y, 0), $metaData->getInt(McEditSchematic::OFFSET_Z, 0));
		}
		//TODO: check why this is behaving weird (offsets seem to be wrong)
		/*else {
			$offset = $nbt->getIntArray("Offset", [0, 0, 0]);
			foreach ($offset as $i => $v) {
				$offset[$i] = \pocketmine\utils\Binary::signInt($v);
			}
			$offset = new Vector3(-$offset[0], -$offset[1], -$offset[2]));
		}*/
		$xSize = $nbt->getShort(self::TAG_WIDTH);
		$ySize = $nbt->getShort(self::TAG_HEIGHT);
		$zSize = $nbt->getShort(self::TAG_LENGTH);
		$target->setPoint($offset->down(World::Y_MIN));
		$target->setPos1(new BlockVector(0, World::Y_MIN, 0));
		$target->setPos2(new BlockVector($xSize - 1, World::Y_MIN + $ySize - 1, $zSize - 1));
		$target->getManager()->loadBetween($target->getPos1(), $target->getPos2());

		switch ($version) {
			case 1:
			case 2:
				$blockDataRaw = $nbt->getTag(self::BLOCK_DATA_LEGACY);
				$paletteData = $nbt->getCompoundTag(self::PALETTE);
				$tiles = $nbt->getTag(self::BLOCK_ENTITY_DATA);
				break;
			case 3:
				$blocks = $nbt->getCompoundTag(self::DATA_BLOCKS);
				if ($blocks === null) {
					throw new UnexpectedValueException("Blocks tag missing");
				}
				$blockDataRaw = $blocks->getTag(self::DATA);
				$paletteData = $blocks->getCompoundTag(self::PALETTE);
				$tiles = $blocks->getTag(self::BLOCK_ENTITY_DATA);
				break;
			default:
				throw new UnexpectedValueException("Unknown schematic version");
		}

		if ($paletteData === null) {
			throw new UnexpectedValueException("Schematic is missing palette");
		}

		if (!$blockDataRaw instanceof AbstractByteArrayTag) {
			throw new UnexpectedValueException("Invalid schematic");
		}

		$javaPalette = [];
		$tilePalette = [];
		/** @var IntTag $id */
		foreach ($paletteData->getValue() as $name => $id) {
			//TODO: Use version of sponge to update states (java state upgrades needed)
			try {
				$javaPalette[$id->getValue()] = BlockStateConvertor::javaToBedrock(BlockParser::fromStateString($name, RepoManager::getVersion()), false, $compound);
				$tilePalette[$id->getValue()] = $compound;
			} catch (Throwable $e) {
				EditThread::getInstance()->debug("Failed to convert block state $name: " . $e->getMessage());
				$javaPalette[$id->getValue()] = GlobalBlockStateHandlers::getUnknownBlockStateData();
				$tilePalette[$id->getValue()] = null;
			}
		}

		$palette = BlockStateTranslationManager::requestRuntimeId($javaPalette);

		if ($tiles instanceof AbstractListTag && $tiles->getTagType() === NBT::TAG_Compound) {
			$tileData = self::loadTileData($tiles, $version);
		}

		$blockData = $blockDataRaw->nextChunk();
		$i = 0;
		$yMax = $ySize + World::Y_MIN;
		for ($y = World::Y_MIN; $y < $yMax; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					if ($i + 5 > AbstractByteArrayTag::CHUNK_SIZE) {
						$blockData = substr($blockData, -5) . $blockDataRaw->nextChunk(-5);
						$i -= AbstractByteArrayTag::CHUNK_SIZE - 5;
					}

					$target->addBlock($x, $y, $z, $palette[$j = Binary::readUnsignedVarInt($blockData, $i)] ?? 0);

					if (isset($tileData[World::blockHash($x, $y, $z)])) {
						$target->addTile(AbstractNBT::fromAbstractTile(TileConvertor::toBedrock($tileData[World::blockHash($x, $y, $z)], $tilePalette[$j] ?? null)));
					}
				}
			}
		}

		//TODO: entities
	}

	/**
	 * @param CompoundTag               $nbt
	 * @param DynamicBlockListSelection $target
	 * @throws CancelException
	 */
	public static function writeFromSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		if (!BlockStateConvertor::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$nbt->setInt(self::FORMAT_VERSION, 2);
		$nbt->setInt(self::UNUSED_DATA_VERSION, 1343); //1.12.2
		$metaData = new CompoundTag();
		$metaData->setInt(McEditSchematic::OFFSET_X, $target->getPoint()->x);
		$metaData->setInt(McEditSchematic::OFFSET_Y, $target->getPoint()->y + World::Y_MIN);
		$metaData->setInt(McEditSchematic::OFFSET_Z, $target->getPoint()->z);
		$nbt->setTag(self::METADATA, $metaData);
		//$nbt->setIntArray("Offset", [-$target->getPoint()->getFloorX(), -$target->getPoint()->getFloorY(), -$target->getPoint()->getFloorZ()]);
		$xSize = $target->getSize()->x;
		$ySize = $target->getSize()->y;
		$zSize = $target->getSize()->z;
		$nbt->setShort(self::TAG_WIDTH, $xSize);
		$nbt->setShort(self::TAG_HEIGHT, $ySize);
		$nbt->setShort(self::TAG_LENGTH, $zSize);

		$tileData = [];
		try {
			foreach ($target->getTiles($target->getPos1(), $target->getPos2()) as $tile) {
				$tileData[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = clone $tile;
			}
		} catch (Throwable $e) {
			throw new UnexpectedValueException("Selection contains unsupported tiles: " . $e->getMessage());
		}

		$blockData = new BinaryStream();
		$tiles = [];
		$palette = [];

		$yMax = $ySize + World::Y_MIN;

		$translation = [];
		/** @var BlockStateData $state */
		foreach ($target->requestBlockStates() as $id => $state) {
			$translation[$id] = BlockParser::toStateString($state);
		}

		for ($y = World::Y_MIN; $y < $yMax; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					$block = $target->getIterator()->getBlock($x, $y, $z);
					$state = $translation[$block];

					if (isset($tileData[World::blockHash($x, $y, $z)])) {
						self::writeTileData($tileData[World::blockHash($x, $y, $z)], $tiles, $state);
					}

					if (!isset($palette[$state])) {
						$palette[$state] = count($palette);
					}

					$blockData->putUnsignedVarInt($palette[$state]);
				}
			}
		}

		$paletteData = new CompoundTag();
		/** @var string $id */
		foreach ($palette as $id => $index) {
			$paletteData->setInt(BlockParser::toStateString(BlockStateConvertor::bedrockToJava(BlockParser::fromStateString($id, BlockStateData::CURRENT_VERSION))), $index);
		}

		//TODO: switch back to v3 whenever java WorldEdit finally supports it
		//$blocks = new CompoundTag();
		//$blocks->setByteArray(self::DATA, $blockData->getBuffer());
		//$blocks->setTag(self::PALETTE, $paletteData);
		//$blocks->setTag(self::BLOCK_ENTITY_DATA, new ListTag($tiles, NBT::TAG_Compound));
		//
		//$nbt->setTag(self::DATA_BLOCKS, $blocks);

		$nbt->setByteArray(self::BLOCK_DATA_LEGACY, $blockData->getBuffer());
		$nbt->setTag(self::PALETTE, $paletteData);
		$nbt->setTag(self::BLOCK_ENTITY_DATA, new ListTag($tiles, NBT::TAG_Compound));

		//TODO: entities
	}

	/**
	 * @param AbstractListTag $tiles
	 * @param int             $version
	 * @return CompoundTag[]
	 */
	private static function loadTileData(AbstractListTag $tiles, int $version): array
	{
		$tileData = [];
		try {
			$count = $tiles->getLength();
			for ($i = 0; $i < $count; $i++) {
				/** @var CompoundTag $tile */
				$tile = $tiles->next();
				$id = $tile->getString(self::ENTITY_ID);
				$pos = $tile->getIntArray(self::ENTITY_POSITION);
				$position = new BlockVector($pos[0], $pos[1] + World::Y_MIN, $pos[2]);
				if ($version === 3) {
					$data = $tile->getCompoundTag(self::ENTITY_EXTRA_DATA) ?? new CompoundTag();
				} else {
					$tile->removeTag(self::ENTITY_ID, self::ENTITY_POSITION);
					$data = $tile;
				}
				$data->setString(Tile::TAG_ID, $id);
				$data->setInt(Tile::TAG_X, $position->x);
				$data->setInt(Tile::TAG_Y, $position->y);
				$data->setInt(Tile::TAG_Z, $position->z);
				$tileData[World::blockHash($position->x, $position->y, $position->z)] = $data;
			}
		} catch (Throwable $e) {
			throw new UnexpectedValueException("Schematic contains malformed tiles: " . $e->getMessage());
		}
		return $tileData;
	}

	/**
	 * @param CompoundTag   $tile
	 * @param CompoundTag[] $tiles
	 * @param string        $state
	 * @return void
	 */
	private static function writeTileData(CompoundTag $tile, array &$tiles, string &$state): void
	{
		if (TileConvertor::toJava($tile, $state)) {
			$id = $tile->getString(Tile::TAG_ID);
			$x = $tile->getInt(Tile::TAG_X);
			$y = $tile->getInt(Tile::TAG_Y) - World::Y_MIN;
			$z = $tile->getInt(Tile::TAG_Z);
			$tile->removeTag(Tile::TAG_ID, Tile::TAG_X, Tile::TAG_Y, Tile::TAG_Z);
			//$data = new CompoundTag();
			//data->setTag(self::ENTITY_EXTRA_DATA, $tile);
			$tile->setString(self::ENTITY_ID, $id);
			$tile->setIntArray(self::ENTITY_POSITION, [$x, $y, $z]);
			$tiles[] = $tile; //filter
		}
	}

	public static function getNbtSerializer(): AbstractNBTSerializer
	{
		return new BigEndianAbstractNBTSerializer();
	}
}