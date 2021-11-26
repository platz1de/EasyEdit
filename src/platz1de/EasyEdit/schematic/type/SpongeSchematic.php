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
	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		$version = $nbt->getInt("Version", 1);
		$offset = new Vector3(0, 0, 0);
		$metaData = $nbt->getCompoundTag("Metadata");
		if ($metaData !== null) {
			$offset = new Vector3(-$nbt->getInt("WEOffsetX", 0), -$nbt->getInt("WEOffsetY", 0), -$nbt->getInt("WEOffsetZ", 0));
		}
		//TODO: check why this is behaving weird (offsets seem to be wrong)
		/*else {
			$offset = $nbt->getIntArray("Offset", [0, 0, 0]);
			foreach ($offset as $i => $v) {
				$offset[$i] = \pocketmine\utils\Binary::signInt($v);
			}
			$offset = new Vector3(-$offset[0], -$offset[1], -$offset[2]));
		}*/
		$xSize = $nbt->getShort("Width");
		$ySize = $nbt->getShort("Height");
		$zSize = $nbt->getShort("Length");
		$target->setPoint($offset);
		$target->setPos1(new Vector3(0, World::Y_MIN, 0));
		$target->setPos2(new Vector3($xSize, $ySize, $zSize));
		$target->getManager()->load($target->getPos1(), $target->getPos2());

		switch ($version) {
			case 1:
			case 2:
				$blockDataRaw = $nbt->getByteArray("BlockData");
				$paletteData = $nbt->getCompoundTag("Palette");
				break;
			case 3:
				$blocks = $nbt->getCompoundTag("Blocks");
				if ($blocks === null) {
					throw new UnexpectedValueException("Blocks tag missing");
				}
				$blockDataRaw = $blocks->getByteArray("Data");
				$paletteData = $blocks->getCompoundTag("Palette");
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
}