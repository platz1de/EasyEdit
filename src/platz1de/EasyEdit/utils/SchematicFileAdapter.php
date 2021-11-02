<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\selection\BlockListSelection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\world\World;
use UnexpectedValueException;

class SchematicFileAdapter
{
	/**
	 * @var string[]
	 */
	private static array $knownExtensions = [".schematic", ".schem", ""];

	public static function readIntoSelection(string $path, BlockListSelection $target): void
	{
		$file = null;
		foreach (self::$knownExtensions as $extension) {
			if (is_file($path . $extension)) {
				$file = file_get_contents($path . $extension);
			}
		}

		if ($file === null || $file === false) {
			throw new UnexpectedValueException("Unknown schematic " . $path);
		}

		$parser = new BigEndianNbtSerializer();
		$nbt = $parser->read(zlib_decode($file))->mustGetCompoundTag();

		//TODO: WEOffset
		$xSize = $nbt->getShort("Width");
		$ySize = $nbt->getShort("Height");
		$zSize = $nbt->getShort("Length");
		$target->setPos1(new Vector3(0, World::Y_MIN, 0));
		$target->setPos2(new Vector3($xSize, $ySize, $zSize));
		$target->getManager()->load($target->getPos1(), $target->getPos2());

		$blockIdData = $nbt->getByteArray("Blocks");
		$blockAdditionalData = $nbt->getByteArray("AddBlocks", "");
		$blockMetaData = $nbt->getByteArray("Data");

		$i = 0;
		//McEdit why this weird order?
		for ($y = 0; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					//TODO: convert block materials
					$id = ord($blockIdData[$i]);
					$additionalData = ord($blockAdditionalData[$i] ?? "");
					$meta = ord($blockMetaData[$i]);

					if ($i % 2 === 1) {
						$id = (($additionalData & 0x0F) << 8) | ($id & 0xFF);
					} else {
						$id = (($additionalData & 0xF0) << 4) | ($id & 0xFF);
					}

					$target->addBlock($x, $y, $z, ($id << Block::INTERNAL_METADATA_BITS) | $meta);
					$i++;
				}
			}
		}

		//TODO: tiles and entities
	}
}