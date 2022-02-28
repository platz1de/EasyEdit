<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\block\Block;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\math\Axis;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Internet;
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
	 * @var array<int, array<int, array{int, int}>>
	 */
	private static array $conversionFrom;

	/**
	 * @var array<string, array{int, int}>
	 */
	private static array $paletteFrom;
	/**
	 * @var array<int, array<int, string>>
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
	 * @var array<string, array{string, CompoundTag}>
	 */
	private static array $reverseCompoundMapping;

	public static function load(string $bedrockSource, string $bedrockPaletteSource, string $javaPaletteSource, string $rotationSource, string $flipSource, string $tileDataSourcePalette): void
	{
		self::$conversionFrom = [];
		self::$paletteFrom = [];
		self::$paletteTo = [];
		self::$rotationData = [];
		self::$flipData = [];
		self::$compoundMapping = [];

		try {
			foreach (self::loadFromSource($bedrockSource) as $javaStringId => $bedrockStringId) {
				$idData = BlockParser::fromStringId($javaStringId);
				self::$conversionFrom[$idData[0]][$idData[1]] = BlockParser::fromStringId($bedrockStringId);
			}
			foreach (self::loadFromSource($bedrockPaletteSource) as $javaState => $bedrockStringId) {
				self::$paletteFrom[$javaState] = BlockParser::fromStringId($bedrockStringId);
			}
			foreach (self::loadFromSource($javaPaletteSource) as $bedrockStringId => $javaState) {
				$idData = BlockParser::fromStringId($bedrockStringId);
				self::$paletteTo[$idData[0]][$idData[1]] = $javaState;
			}
			foreach (self::loadFromSource($rotationSource) as $preRotationId => $pastRotationId) {
				$idData = BlockParser::fromStringId($preRotationId);
				$rotatedIdData = BlockParser::fromStringId($pastRotationId);
				self::$rotationData[$idData[0] << Block::INTERNAL_METADATA_BITS | $idData[1]] = $rotatedIdData[0] << Block::INTERNAL_METADATA_BITS | $rotatedIdData[1];
			}
			foreach (self::loadFromSourceComplex($flipSource) as $axisName => $axisFlips) {
				$axis = match ($axisName) {
					"xAxis" => Axis::X,
					"yAxis" => Axis::Y,
					"zAxis" => Axis::Z,
					default => throw new UnexpectedValueException("Unknown axis name $axisName")
				};
				foreach ($axisFlips as $preFlipId => $pastFlipId) {
					$idData = BlockParser::fromStringId($preFlipId);
					$flippedIdData = BlockParser::fromStringId($pastFlipId);
					self::$flipData[$axis][$idData[0] << Block::INTERNAL_METADATA_BITS | $idData[1]] = $flippedIdData[0] << Block::INTERNAL_METADATA_BITS | $flippedIdData[1];
				}
			}
			$tileDataPalette = self::loadFromSourceComplex($tileDataSourcePalette);
			if (!isset($tileDataPalette[TileConvertor::DATA_CHEST_RELATION])) {
				EditThread::getInstance()->debug("Couldn't find chest relation data");
			}
			/** @var string $state */
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
			/** @var string $state */
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
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
		}
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
				throw $err;
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
	 * @param string $source
	 * @return string[][]
	 * @throws Throwable
	 */
	private static function loadFromSourceComplex(string $source): array
	{
		/**@phpstan-ignore-next-line we can't handle this properly */
		return self::loadFromSource($source);
	}

	/**
	 * @param int $id
	 * @param int $meta
	 */
	public static function convertFromJava(int &$id, int &$meta): void
	{
		[$id, $meta] = self::$conversionFrom[$id][$meta] ?? [$id, $meta];
	}

	/**
	 * @param string $state
	 * @return array{int, int}
	 */
	public static function getFromState(string $state): array
	{
		if (!isset(self::$paletteFrom[$state])) {
			EditThread::getInstance()->getLogger()->debug("Requested unknown state " . $state);
		}
		return self::$paletteFrom[$state] ?? [0, 0];
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
	 * @param int $id
	 * @param int $meta
	 * @return string
	 */
	public static function getState(int $id, int $meta): string
	{
		return self::$paletteTo[$id][$meta] ?? self::$paletteTo[0][0];
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
}