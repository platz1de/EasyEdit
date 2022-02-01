<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\block\Block;
use pocketmine\block\tile\Chest;
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

		//This should only be executed on edit thread
		$bedrockData = Internet::getURL($bedrockSource, 10, [], $err);
		$bedrockPaletteData = Internet::getURL($bedrockPaletteSource, 10, [], $err);
		$javaPaletteData = Internet::getURL($javaPaletteSource, 10, [], $err);
		$rotationData = Internet::getURL($rotationSource, 10, [], $err);
		$flipData = Internet::getURL($flipSource, 10, [], $err);
		$tileDataPaletteData = Internet::getURL($tileDataSourcePalette, 10, [], $err);
		if ($bedrockData === null || $bedrockPaletteData === null || $javaPaletteData === null || $rotationData === null || $flipData === null || $tileDataPaletteData === null) {
			EditThread::getInstance()->getLogger()->error("Failed to load conversion data, schematic conversion is not available");
			if (isset($err)) {
				EditThread::getInstance()->getLogger()->logException($err);
			}
			return;
		}

		try {
			$bedrockBlocks = json_decode($bedrockData->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$bedrockPalette = json_decode($bedrockPaletteData->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$javaPalette = json_decode($javaPaletteData->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$rotations = json_decode($rotationData->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$flips = json_decode($flipData->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$tileDataPalette = json_decode($tileDataPaletteData->getBody(), true, 512, JSON_THROW_ON_ERROR);

			if (!is_array($bedrockBlocks) || !is_array($bedrockPalette) || !is_array($javaPalette) || !is_array($rotations) || !is_array($flips) || !is_array($tileDataPalette)) {
				throw new UnexpectedValueException("Conversion data does not represent arrays");
			}
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
			return;
		}

		foreach ($bedrockBlocks as $javaStringId => $bedrockStringId) {
			$idData = BlockParser::fromStringId($javaStringId);
			self::$conversionFrom[$idData[0]][$idData[1]] = BlockParser::fromStringId($bedrockStringId);
		}
		/** @var string $javaState */
		foreach ($bedrockPalette as $javaState => $bedrockStringId) {
			self::$paletteFrom[$javaState] = BlockParser::fromStringId($bedrockStringId);
		}
		foreach ($javaPalette as $bedrockStringId => $javaState) {
			$idData = BlockParser::fromStringId($bedrockStringId);
			self::$paletteTo[$idData[0]][$idData[1]] = $javaState;
		}
		foreach ($rotations as $preRotationId => $pastRotationId) {
			$idData = BlockParser::fromStringId($preRotationId);
			$rotatedIdData = BlockParser::fromStringId($pastRotationId);
			self::$rotationData[$idData[0] << Block::INTERNAL_METADATA_BITS | $idData[1]] = $rotatedIdData[0] << Block::INTERNAL_METADATA_BITS | $rotatedIdData[1];
		}
		foreach ($flips as $axisName => $axisFlips) {
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
	 * @param string         $state
	 * @param ConvertorCache $cache
	 * @return array{int, int}
	 */
	public static function getFromState(string $state, ConvertorCache $cache): array
	{
		if (!isset(self::$paletteFrom[$state])) {
			EditThread::getInstance()->getLogger()->debug("Requested unknown state " . $state);
		}
		return self::$paletteFrom[$state] ?? [0, 0];
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