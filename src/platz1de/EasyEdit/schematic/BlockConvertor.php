<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
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

	public static function load(string $bedrockSource, string $bedrockPaletteSource, string $javaPaletteSource): void
	{
		self::$conversionFrom = [];
		self::$paletteFrom = [];
		self::$paletteTo = [];

		//This should only be executed on edit thread
		$bedrockData = Internet::getURL($bedrockSource, 10, [], $err);
		$bedrockPaletteData = Internet::getURL($bedrockPaletteSource, 10, [], $err);
		$javaPaletteData = Internet::getURL($javaPaletteSource, 10, [], $err);
		if ($bedrockData === null || $bedrockPaletteData === null || $javaPaletteData === null) {
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

			if (!is_array($bedrockBlocks) || !is_array($bedrockPalette) || !is_array($javaPalette)) {
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
	 * @param int $id
	 * @param int $meta
	 * @return string
	 */
	public static function getState(int $id, int $meta): string
	{
		return self::$paletteTo[$id][$meta] ?? self::$paletteTo[0][0];
	}
}