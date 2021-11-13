<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\utils\Internet;
use Throwable;

/**
 * Convertor between java 1.12.2 ids and bedrocks current ids
 * Only intended for use in McEdit schematic file conversion
 */
class BlockConvertor
{
	/**
	 * @var array<int, array<int, array{int, int}>>
	 */
	private static array $conversionFrom;
	/**
	 * @var array<int, array<int, array{int, int}>>
	 */
	private static array $conversionTo;

	public static function load(string $bedrockSource, string $javaSource): void
	{
		self::$conversionFrom = [];
		self::$conversionTo = [];

		//This should only be executed on edit thread
		$bedrockData = Internet::getURL($bedrockSource, 10, [], $err);
		$javaData = Internet::getURL($javaSource, 10, [], $err);
		if ($bedrockData === null || $javaData === null) {
			EditThread::getInstance()->getLogger()->error("Failed to load conversion data, schematic conversion is not available");
			if (isset($err)) {
				EditThread::getInstance()->getLogger()->logException($err);
			}
			return;
		}

		try {
			$bedrockBlocks = json_decode($bedrockData->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$javaBlocks = json_decode($javaData->getBody(), true, 512, JSON_THROW_ON_ERROR);
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
			return;
		}

		foreach ($bedrockBlocks as $javaStringId => $bedrockStringId) {
			$idData = BlockParser::fromStringId($javaStringId);
			self::$conversionFrom[$idData[0]][$idData[1]] = BlockParser::fromStringId($bedrockStringId);
		}
		foreach ($javaBlocks as $bedrockStringId => $javaStringId) {
			$idData = BlockParser::fromStringId($bedrockStringId);
			self::$conversionTo[$idData[0]][$idData[1]] = BlockParser::fromStringId($javaStringId);
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
	 * @param int $id
	 * @param int $meta
	 */
	public static function convertToJava(int &$id, int &$meta): void
	{
		[$id, $meta] = self::$conversionTo[$id][$meta] ?? [$id, $meta];
		if ($id >= (1 << 8)) { //java 1.12 only supports 256 block ids, this also removes the need to add new block ids to the conversion data
			$id = 0;
			$meta = 0;
		}
	}
}