<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\schematic\nbt\AbstractCompoundTag;
use platz1de\EasyEdit\schematic\nbt\AbstractNBTSerializer;
use platz1de\EasyEdit\schematic\type\McEditSchematic;
use platz1de\EasyEdit\schematic\type\SchematicType;
use platz1de\EasyEdit\schematic\type\SpongeSchematic;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use UnexpectedValueException;

class SchematicFileAdapter
{
	/**
	 * @var class-string<SchematicType>[]
	 */
	private static array $knownExtensions = [
		".schematic" => McEditSchematic::class,
		".schem" => SpongeSchematic::class
	];

	public static function readIntoSelection(string $path, DynamicBlockListSelection $target): void
	{
		$file = null;
		$usedParser = null;
		foreach (self::$knownExtensions as $extension => $parser) {
			if (is_file($path . $extension)) {
				$file = $path . $extension;
				$usedParser = $parser;
				break;
			}
		}

		if ($usedParser === null || $file === null) {
			throw new UnexpectedValueException("Unknown schematic " . $path);
		}

		$nbtParser = new AbstractNBTSerializer();
		$nbt = $nbtParser->readFile($file)->mustGetCompoundTag();

		$usedParser::readIntoSelection($nbt, $target);

		$nbtParser->close();
	}

	public static function createFromSelection(string $path, DynamicBlockListSelection $target): void
	{
		if (!str_ends_with($path, ".schem")) {
			$path .= ".schem";
		}

		if (is_file($path)) {
			unlink($path);
			EditThread::getInstance()->getLogger()->debug("Deleted old schematic file " . $path);
		}

		$nbt = new CompoundTag();
		SpongeSchematic::writeFromSelection($nbt, $target);

		$nbtParser = new BigEndianNbtSerializer();
		$data = zlib_encode($nbtParser->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);
		if ($data === false) {
			throw new UnexpectedValueException("Failed to compress schematic data");
		}
		file_put_contents($path, $data);
	}

	public static function schematicExists(string $path): bool
	{
		foreach (self::$knownExtensions as $extension => $parser) {
			if (is_file($path . $extension)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	public static function getSchematicList(): array
	{
		$schematics = [];
		$fileList = scandir(EasyEdit::getSchematicPath());
		if ($fileList === false) {
			return [];
		}
		foreach ($fileList as $file) {
			foreach (self::$knownExtensions as $extension => $parser) {
				if (str_ends_with($file, $extension)) {
					$schematics[] = pathinfo($file, PATHINFO_FILENAME);
				}
			}
		}
		return $schematics;
	}
}