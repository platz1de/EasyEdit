<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\schematic\nbt\AbstractListTag;
use platz1de\EasyEdit\schematic\nbt\AbstractNBT;
use platz1de\EasyEdit\schematic\nbt\AbstractNBTSerializer;
use platz1de\EasyEdit\schematic\nbt\LittleEndianAbstractNBTSerializer;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\World;
use UnexpectedValueException;

class McStructureSchematic extends SchematicType
{
	public const FORMAT_VERSION = "format_version"; //Always 1
	public const WORLD_ORIGIN = "structure_world_origin";
	public const SIZE = "size";
	public const STRUCTURE = "structure";
	public const PALETTE = "palette";
	public const DEFAULT_PALETTE = "default";
	public const BLOCK_INDEX = "block_indices";
	public const BLOCK_PALETTE = "block_palette";
	public const TILE_INDEX = "block_position_data";
	public const TILE_ENTRY = "block_entity_data";

	/**
	 * @param CompoundTag               $nbt
	 * @param DynamicBlockListSelection $target
	 * @throws CancelException
	 */
	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		$size = $nbt->getTag(self::SIZE);
		$offset = $nbt->getTag(self::WORLD_ORIGIN);
		if (!$size instanceof AbstractListTag || $size->getTagType() !== NBT::TAG_Int || $size->getLength() !== 3 || !$offset instanceof AbstractListTag || $offset->getTagType() !== NBT::TAG_Int || $offset->getLength() !== 3) {
			throw new UnexpectedValueException("Invalid schematic file");
		}
		$xSize = $size->mustGetInt();
		$ySize = $size->mustGetInt();
		$zSize = $size->mustGetInt();
		$target->setPoint(new BlockOffsetVector(0, -World::Y_MIN, 0));
		$target->setPos1(new BlockVector(0, World::Y_MIN, 0));
		$target->setPos2(new BlockVector($xSize, World::Y_MIN + $ySize, $zSize));
		$target->getManager()->loadBetween($target->getPos1(), $target->getPos2());

		$offsetX = $offset->mustGetInt();
		$offsetY = $offset->mustGetInt();
		$offsetZ = $offset->mustGetInt();

		$structure = $nbt->getCompoundTag(self::STRUCTURE);
		$palette = $structure?->getCompoundTag(self::PALETTE)?->getCompoundTag(self::DEFAULT_PALETTE);
		$blockDataRaw = $structure?->getTag(self::BLOCK_INDEX);

		if ($palette === null) {
			throw new UnexpectedValueException("Schematic is missing palette");
		}

		if (!$blockDataRaw instanceof AbstractListTag) {
			throw new UnexpectedValueException("Invalid schematic file");
		}

		$blockData = $blockDataRaw->next(); //First layer, we ignore the second one

		$blockPalette = $palette->getTag(self::BLOCK_PALETTE);
		$tileData = $palette->getCompoundTag(self::TILE_INDEX) ?? new CompoundTag();

		if (!$blockPalette instanceof AbstractListTag || $blockPalette->getTagType() !== NBT::TAG_Compound || !$blockData instanceof AbstractListTag) {
			throw new UnexpectedValueException("Invalid schematic file");
		}

		$palette = [];
		while ($blockPalette->hasNext()) {
			$block = $blockPalette->next();
			if (!$block instanceof CompoundTag) {
				throw new UnexpectedValueException("Invalid schematic file");
			}
			$palette[] = GlobalBlockStateHandlers::getUpgrader()->upgradeBlockStateNbt($block);
		}

		$palette = BlockStateTranslationManager::requestRuntimeId($palette);

		$i = 0;
		$yMax = $ySize + World::Y_MIN;
		for ($x = 0; $x < $xSize; ++$x) {
			for ($y = World::Y_MIN; $y < $yMax; ++$y) {
				for ($z = 0; $z < $zSize; ++$z) {
					$target->addBlock($x, $y, $z, $palette[$blockData->mustGetInt()]);

					$tile = $tileData->getCompoundTag((string) $i++);
					if ($tile !== null) {
						$tileEntry = AbstractNBT::fromAbstractTile($tile->getCompoundTag(self::TILE_ENTRY));
						if ($tileEntry === null) {
							throw new UnexpectedValueException("Invalid schematic file");
						}
						$target->addTile(TileUtils::offsetCompound($tileEntry, -$offsetX, -$offsetY + World::Y_MIN, -$offsetZ));
					}
				}
			}
		}

		//TODO: entities
	}

	public static function getNbtSerializer(): AbstractNBTSerializer
	{
		return new LittleEndianAbstractNBTSerializer();
	}
}