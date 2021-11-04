<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\schematic\type\McEditSchematic;
use platz1de\EasyEdit\schematic\type\SchematicType;
use platz1de\EasyEdit\selection\BlockListSelection;
use pocketmine\nbt\BigEndianNbtSerializer;
use UnexpectedValueException;

class SchematicFileAdapter
{
	/**
	 * @var class-string<SchematicType>[]
	 */
	private static array $knownExtensions = [
		".schematic" => McEditSchematic::class
	];

	public static function readIntoSelection(string $path, BlockListSelection $target): void
	{
		$file = null;
		$usedParser = null;
		foreach (self::$knownExtensions as $extension => $parser) {
			if (is_file($path . $extension)) {
				$file = file_get_contents($path . $extension);
				$usedParser = $parser;
			}
		}

		if ($usedParser === null || $file === null || $file === false || ($file = zlib_decode($file)) === false) {
			throw new UnexpectedValueException("Unknown schematic " . $path);
		}

		$nbtParser = new BigEndianNbtSerializer();
		$nbt = $nbtParser->read($file)->mustGetCompoundTag();

		$usedParser::readIntoSelection($nbt, $target);
	}
}