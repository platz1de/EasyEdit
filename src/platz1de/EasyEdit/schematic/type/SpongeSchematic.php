<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\schematic\BlockConvertor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\BinaryStream;
use pocketmine\world\World;
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
	public const UNUSED_DATA_VERSION = "DataVersion";

	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		$version = $nbt->getInt(self::FORMAT_VERSION, 1);
		$offset = new Vector3(0, 0, 0);
		$metaData = $nbt->getCompoundTag(self::METADATA);
		if ($metaData !== null) {
			$offset = new Vector3(-$metaData->getInt(McEditSchematic::OFFSET_X, 0), -$metaData->getInt(McEditSchematic::OFFSET_Y, 0), -$metaData->getInt(McEditSchematic::OFFSET_Z, 0));
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
		$target->setPoint($offset);
		$target->setPos1(new Vector3(0, World::Y_MIN, 0));
		$target->setPos2(new Vector3($xSize, World::Y_MIN + $ySize, $zSize));
		$target->getManager()->load($target->getPos1(), $target->getPos2());

		switch ($version) {
			case 1:
			case 2:
				$blockDataRaw = $nbt->getByteArray(self::BLOCK_DATA_LEGACY);
				$paletteData = $nbt->getCompoundTag(self::PALETTE);
				break;
			case 3:
				$blocks = $nbt->getCompoundTag(self::DATA_BLOCKS);
				if ($blocks === null) {
					throw new UnexpectedValueException("Blocks tag missing");
				}
				$blockDataRaw = $blocks->getByteArray(self::DATA);
				$paletteData = $blocks->getCompoundTag(self::PALETTE);
				break;
			default:
				throw new UnexpectedValueException("Unknown schematic version");
		}
		if ($paletteData === null) {
			throw new UnexpectedValueException("Schematic is missing palette");
		}
		$palette = [];
		foreach ($paletteData->getValue() as $name => $id) {
			$palette[$id->getValue()] = BlockConvertor::getFromState($name);
		}

		$blockData = new BinaryStream($blockDataRaw);

		for ($y = 0; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					[$id, $meta] = $palette[$blockData->getUnsignedVarInt()];

					$target->addBlock($x, $y, $z, ($id << Block::INTERNAL_METADATA_BITS) | $meta);
				}
			}
		}

		//TODO: tiles and entities
	}

	public static function writeFromSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		$nbt->setInt(self::FORMAT_VERSION, 3);
		$nbt->setInt(self::UNUSED_DATA_VERSION, 1343); //1.12.2
		$metaData = new CompoundTag();
		$metaData->setInt(McEditSchematic::OFFSET_X, -$target->getPoint()->getFloorX());
		$metaData->setInt(McEditSchematic::OFFSET_Y, -$target->getPoint()->getFloorY());
		$metaData->setInt(McEditSchematic::OFFSET_Z, -$target->getPoint()->getFloorZ());
		$nbt->setTag(self::METADATA, $metaData);
		//$nbt->setIntArray("Offset", [-$target->getPoint()->getFloorX(), -$target->getPoint()->getFloorY(), -$target->getPoint()->getFloorZ()]);
		$xSize = $target->getSize()->getFloorX();
		$ySize = $target->getSize()->getFloorY();
		$zSize = $target->getSize()->getFloorZ();
		$nbt->setShort(self::TAG_WIDTH, $xSize);
		$nbt->setShort(self::TAG_HEIGHT, $ySize);
		$nbt->setShort(self::TAG_LENGTH, $zSize);

		$blockData = new BinaryStream();
		$palette = [];

		for ($y = 0; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					$block = $target->getIterator()->getBlockAt($x, $y, $z);

					if (!isset($palette[$block])) {
						$palette[$block] = count($palette);
					}

					$blockData->putUnsignedVarInt($palette[$block]);
				}
			}
		}

		$paletteData = new CompoundTag();
		foreach ($palette as $id => $index) {
			$paletteData->setInt(BlockConvertor::getState($id >> Block::INTERNAL_METADATA_BITS, $id & Block::INTERNAL_METADATA_MASK), $index);
		}

		$blocks = new CompoundTag();
		$blocks->setByteArray(self::DATA, $blockData->getBuffer());
		$blocks->setTag(self::PALETTE, $paletteData);

		$nbt->setTag(self::DATA_BLOCKS, $blocks);

		//TODO: tiles and entities
	}
}