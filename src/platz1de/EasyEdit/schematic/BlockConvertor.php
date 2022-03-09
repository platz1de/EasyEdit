<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResourceData;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\math\Axis;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use Throwable;
use UnexpectedValueException;

/**
 * Convertor between java 1.12.2 ids and bedrocks current ids
 * Only intended for use in McEdit schematic file conversion
 *
 * Convertor between java block states and bedrocks current ids
 */
class BlockConvertor
{
	/**
	 * @var array<int, int>
	 */
	private static array $conversionFrom;

	/**
	 * @var array<string, int>
	 */
	private static array $paletteFrom;
	/**
	 * @var array<int, string>
	 */
	private static array $paletteTo;
	/**
	 * @var array<int, int>
	 */
	private static array $rotationData;
	/**
	 * @var array<int, array<int, int>>
	 */
	private static array $flipData;
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

	public static function load(string $bedrockSource, string $bedrockPaletteSource, string $javaPaletteSource, string $rotationSource, string $flipSource, string $tileDataSourcePalette, string $javaTileSource): void
	{
		self::$conversionFrom = [];
		self::$paletteFrom = [];
		self::$paletteTo = [];
		self::$rotationData = [];
		self::$flipData = [];
		self::$compoundMapping = [];
		self::$compoundTagKeys = [];
		self::$reverseCompoundMapping = [];

		try {
			foreach (self::loadFromSource($bedrockSource) as $javaStringId => $bedrockStringId) {
				self::$conversionFrom[BlockParser::fromStringId($javaStringId)] = BlockParser::fromStringId($bedrockStringId);
			}
			foreach (self::loadFromSource($bedrockPaletteSource) as $javaState => $bedrockStringId) {
				self::$paletteFrom[$javaState] = BlockParser::fromStringId($bedrockStringId);
			}
			foreach (self::loadFromSource($javaPaletteSource) as $bedrockStringId => $javaState) {
				self::$paletteTo[BlockParser::fromStringId($bedrockStringId)] = $javaState;
			}
			foreach (self::loadFromSource($rotationSource) as $preRotationId => $pastRotationId) {
				self::$rotationData[BlockParser::fromStringId($preRotationId)] = BlockParser::fromStringId($pastRotationId);
			}
			/** @var string[] $axisFlips */
			foreach (self::loadFromSource($flipSource) as $axisName => $axisFlips) {
				$axis = match ($axisName) {
					"xAxis" => Axis::X,
					"yAxis" => Axis::Y,
					"zAxis" => Axis::Z,
					default => throw new UnexpectedValueException("Unknown axis name $axisName")
				};
				foreach ($axisFlips as $preFlipId => $pastFlipId) {
					self::$flipData[$axis][BlockParser::fromStringId($preFlipId)] = BlockParser::fromStringId($pastFlipId);
				}
			}
			/** @var array<string, array<string, string>> $tileDataPalette */
			$tileDataPalette = self::loadFromSource($tileDataSourcePalette);
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

			/** @var array<string, array<string, array<string, string>>> $javaTilePalette */
			$javaTilePalette = self::loadFromSource($javaTileSource);
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
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
		}

		ResourceData::from();
	}

	/**
	 * @param string $source
	 * @return array<string, string>
	 * @throws Throwable
	 */
	private static function loadFromSource(string $source): array
	{
		//This should only be executed on edit thread
		$data = Internet::getURL($source, 10, [], $err);
		if ($data === null) {
			if (isset($err)) {
				throw new InternetException($err);
			}
			return [];
		}

		$parsed = json_decode($data->getBody(), true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($parsed)) {
			throw new UnexpectedValueException("Conversion data does not represent arrays");
		}

		return $parsed;
	}

	/**
	 * @param int $id
	 * @return int
	 */
	public static function convertFromJava(int $id): int
	{
		return self::$conversionFrom[$id] ?? $id;
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
			if ($value === null) {
				return $state;
			}
			$values[] = (string) $value->getValue();
			$tag->removeTag($key);
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

	/**
	 * @param int $id
	 * @return int
	 */
	public static function rotate(int $id): int
	{
		return self::$rotationData[$id] ?? $id;
	}

	/**
	 * @param int $axis
	 * @param int $id
	 * @return int
	 */
	public static function flip(int $axis, int $id): int
	{
		return self::$flipData[$axis][$id] ?? $id;
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
}