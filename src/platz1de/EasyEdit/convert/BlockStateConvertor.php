<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResourceData;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\block\utils\SkullType;
use pocketmine\nbt\tag\CompoundTag;
use Throwable;
use UnexpectedValueException;

/**
 * Convertor between java block states and bedrocks current ids
 */
class BlockStateConvertor
{
	/**
	 * @var array<string, int>
	 */
	private static array $paletteFrom;
	/**
	 * @var array<int, string>
	 */
	private static array $paletteTo;
	/**
	 * @var array<string, CompoundTag>
	 */
	private static array $compoundMapping;
	/**
	 * @var array<string, string[]>
	 */
	private static array $compoundTagKeys;
	/**
	 * @var array<string, array<string, string>>
	 */
	private static array $reverseCompoundMapping;
	private static bool $available = false;

	public static function load(string $bedrockPaletteSource, string $javaPaletteSource, string $tileDataSourcePalette, string $javaTileSource): void
	{
		self::$paletteFrom = [];
		self::$paletteTo = [];
		self::$compoundMapping = [];
		self::$compoundTagKeys = [];
		self::$reverseCompoundMapping = [];

		try {
			/** @var string $bedrockStringId */
			foreach (MixedUtils::getJsonData($bedrockPaletteSource, 2) as $javaState => $bedrockStringId) {
				self::$paletteFrom[$javaState] = BlockParser::fromStringId($bedrockStringId);
			}
			/** @var string $javaState */
			foreach (MixedUtils::getJsonData($javaPaletteSource, 2) as $bedrockStringId => $javaState) {
				self::$paletteTo[BlockParser::fromStringId($bedrockStringId)] = $javaState;
			}
			/** @var array<string, array<string, string>> $tileDataPalette */
			$tileDataPalette = MixedUtils::getJsonData($tileDataSourcePalette, 3);
			if (!isset($tileDataPalette[TileConvertor::DATA_CHEST_RELATION])) {
				EditThread::getInstance()->debug("Couldn't find chest relation data");
			}
			foreach ($tileDataPalette[TileConvertor::DATA_CHEST_RELATION] ?? [] as $state => $data) {
				self::$compoundMapping[$state] = CompoundTag::create()
					->setInt(Chest::TAG_PAIRX, match ($data) {
						"east" => 1,
						"west" => -1,
						default => 0
					})
					->setInt(Chest::TAG_PAIRZ, match ($data) {
						"north" => -1,
						"south" => 1,
						default => 0
					});
			}
			foreach ($tileDataPalette[TileConvertor::DATA_SHULKER_BOX_FACING] ?? [] as $state => $data) {
				self::$compoundMapping[$state] = CompoundTag::create()
					->setByte(ShulkerBox::TAG_FACING, match ($data) {
						"down" => 0,
						"up" => 1,
						"north" => 2,
						"south" => 3,
						"west" => 4,
						"east" => 5,
						default => throw new UnexpectedValueException("Unknown facing $data")
					});
			}
			foreach ($tileDataPalette[TileConvertor::DATA_SKULL_TYPE] ?? [] as $state => $data) {
				self::$compoundMapping[$state] = CompoundTag::create()
					->setByte("SkullType", SkullType::getAll()[mb_strtoupper($data)]->getMagicNumber());
			}
			foreach ($tileDataPalette[TileConvertor::DATA_SKULL_ROTATION] ?? [] as $state => $data) {
				self::$compoundMapping[$state]?->setByte("Rot", (int) $data);
			}

			/** @var array<string, array<string, array<string, string>>> $javaTilePalette */
			$javaTilePalette = MixedUtils::getJsonData($javaTileSource, 4);
			foreach ($javaTilePalette[TileConvertor::DATA_CHEST_RELATION] ?? [] as $state => $data) {
				self::$compoundTagKeys[$state] = [Chest::TAG_PAIRX, Chest::TAG_PAIRZ];
				foreach ($data as $type => $result) {
					switch ($type) {
						case "east":
							self::$reverseCompoundMapping[$state]["1;0"] = $result;
							break;
						case "west":
							self::$reverseCompoundMapping[$state]["-1;0"] = $result;
							break;
						case "north":
							self::$reverseCompoundMapping[$state]["0;-1"] = $result;
							break;
						case "south":
							self::$reverseCompoundMapping[$state]["0;1"] = $result;
							break;
						default:
							throw new UnexpectedValueException("Unknown direction $type");
					}
				}
			}
			foreach ($javaTilePalette[TileConvertor::DATA_SHULKER_BOX_FACING] ?? [] as $state => $data) {
				self::$compoundTagKeys[$state] = [ShulkerBox::TAG_FACING];
				foreach ($data as $type => $result) {
					switch ($type) {
						case "down":
							self::$reverseCompoundMapping[$state]["0"] = $result;
							break;
						case "up":
							self::$reverseCompoundMapping[$state]["1"] = $result;
							break;
						case "north":
							self::$reverseCompoundMapping[$state]["2"] = $result;
							break;
						case "south":
							self::$reverseCompoundMapping[$state]["3"] = $result;
							break;
						case "west":
							self::$reverseCompoundMapping[$state]["4"] = $result;
							break;
						case "east":
							self::$reverseCompoundMapping[$state]["5"] = $result;
							break;
						default:
							throw new UnexpectedValueException("Unknown facing $type");
					}
				}
			}
			foreach ($javaTilePalette[TileConvertor::DATA_SKULL_TYPE] ?? [] as $state => $data) {
				self::$compoundTagKeys[$state] = ["SkullType", "Rot"];
				foreach ($data as $type => $result) {
					if (isset($javaTilePalette[TileConvertor::DATA_SKULL_ROTATION][$result])) {
						/** @var int $rotation */
						foreach ($javaTilePalette[TileConvertor::DATA_SKULL_ROTATION][$result] as $rotation => $rotated) {
							self::$reverseCompoundMapping[$state][((string) SkullType::getAll()[mb_strtoupper($type)]->getMagicNumber()) . ";" . ((string) $rotation)] = $rotated;
						}
					} else {
						self::$reverseCompoundMapping[$state][((string) SkullType::getAll()[mb_strtoupper($type)]->getMagicNumber())] = $result;
						self::$reverseCompoundMapping[$state][((string) SkullType::getAll()[mb_strtoupper($type)]->getMagicNumber()) . ";0"] = $result;
					}
				}
			}
			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse state data, Sponge schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
		}

		ResourceData::from();
	}

	/**
	 * @param string $state
	 * @return int
	 */
	public static function getFromState(string $state): int
	{
		if (!isset(self::$paletteFrom[$state])) {
			EditThread::getInstance()->getLogger()->debug("Requested unknown state " . $state);
		}
		return self::$paletteFrom[$state] ?? 0;
	}

	/**
	 * @param string $name
	 * @return CompoundTag|null
	 */
	public static function getTileDataFromState(string $name): ?CompoundTag
	{
		return self::$compoundMapping[$name] ?? null;
	}

	/**
	 * @param string      $state
	 * @param CompoundTag $tag
	 * @return string
	 */
	public static function processTileData(string $state, CompoundTag $tag): string
	{
		if (!isset(self::$compoundTagKeys[$state])) {
			return $state;
		}
		$values = [];
		foreach (self::$compoundTagKeys[$state] as $key) {
			$value = $tag->getTag($key);
			if ($value !== null) {
				$values[] = (string) $value->getValue();
				$tag->removeTag($key);
			}
		}
		if ($values === []) {
			return $state;
		}
		if (!isset(self::$reverseCompoundMapping[$state][implode(";", $values)])) {
			EditThread::getInstance()->debug("Unknown state $state with magical values " . implode(";", $values));
			return $state;
		}
		return self::$reverseCompoundMapping[$state][implode(";", $values)];
	}

	/**
	 * @param int $id
	 * @return string
	 */
	public static function getState(int $id): string
	{
		return self::$paletteTo[$id] ?? self::$paletteTo[0];
	}

	public static function getResourceData(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putInt(count(self::$paletteFrom));
		foreach (self::$paletteFrom as $state => $id) {
			$stream->putString($state);
			$stream->putInt($id);
		}
		$stream->putInt(count(self::$paletteTo));
		foreach (self::$paletteTo as $id => $state) {
			$stream->putInt($id);
			$stream->putString($state);
		}
		return $stream->getBuffer();
	}

	public static function loadResourceData(string $data): void
	{
		$stream = new ExtendedBinaryStream($data);
		self::$paletteFrom = [];
		self::$paletteTo = [];
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			self::$paletteFrom[$stream->getString()] = $stream->getInt();
		}
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			self::$paletteTo[$stream->getInt()] = $stream->getString();
		}
	}

	/**
	 * @return string[]
	 */
	public static function getAllKnownStates(): array
	{
		return self::$paletteTo;
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}