<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\schematic\BlockConvertor;
use platz1de\EasyEdit\selection\BlockListSelection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class McEditSchematic extends SchematicType
{
	public static function readIntoSelection(CompoundTag $nbt, BlockListSelection $target): void
	{
		//TODO: WEOffset
		$xSize = $nbt->getShort("Width");
		$ySize = $nbt->getShort("Height");
		$zSize = $nbt->getShort("Length");
		$target->setPos1(new Vector3(0, World::Y_MIN, 0));
		$target->setPos2(new Vector3($xSize, $ySize, $zSize));
		$target->getManager()->load($target->getPos1(), $target->getPos2());

		//"AddBlocks" allows ids over 255
		//this can be ignored as java pre-flattening only had 255 block ids in use and later didn't support block ids at all
		$blockIdData = $nbt->getByteArray("Blocks");
		$blockMetaData = $nbt->getByteArray("Data");

		$i = 0;
		//McEdit why this weird order?
		for ($y = 0; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					$id = ord($blockIdData[$i]);
					$meta = ord($blockMetaData[$i]);

					BlockConvertor::convert($id, $meta);

					$target->addBlock($x, $y, $z, ($id << Block::INTERNAL_METADATA_BITS) | $meta);
					$i++;
				}
			}
		}

		//TODO: tiles and entities
	}
}