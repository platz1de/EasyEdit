<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\convert\LegacyBlockIdConvertor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\InternetException;
use pocketmine\world\World;

class McEditSchematic extends SchematicType
{
	public const OFFSET_X = "WEOffsetX";
	public const OFFSET_Y = "WEOffsetY";
	public const OFFSET_Z = "WEOffsetZ";
	public const BLOCK_ID = "Blocks";
	public const BLOCK_META = "Data";

	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		if (!LegacyBlockIdConvertor::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$xSize = $nbt->getShort(self::TAG_WIDTH);
		$ySize = $nbt->getShort(self::TAG_HEIGHT);
		$zSize = $nbt->getShort(self::TAG_LENGTH);
		$target->setPoint(new Vector3($nbt->getInt(self::OFFSET_X, 0), $nbt->getInt(self::OFFSET_Y, 0), $nbt->getInt(self::OFFSET_Z, 0)));
		$target->setPos1(new Vector3(0, World::Y_MIN, 0));
		$target->setPos2(new Vector3($xSize, World::Y_MIN + $ySize, $zSize));
		$target->getManager()->loadBetween($target->getPos1(), $target->getPos2());

		//"AddBlocks" allows ids over 255
		//this can be ignored as java pre-flattening only had 255 block ids in use and later didn't support block ids at all
		$blockIdData = $nbt->getByteArray(self::BLOCK_ID);
		$blockMetaData = $nbt->getByteArray(self::BLOCK_META);

		$i = 0;
		//McEdit why this weird order?
		for ($y = 0; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					$id = ord($blockIdData[$i]);
					$meta = ord($blockMetaData[$i]);

					$target->addBlock($x, $y, $z, LegacyBlockIdConvertor::convertFromJava(($id << Block::INTERNAL_METADATA_BITS) | $meta));
					$i++;
				}
			}
		}

		//TODO: tiles and entities
	}
}