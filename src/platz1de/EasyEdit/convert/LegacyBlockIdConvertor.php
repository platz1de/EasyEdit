<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\MixedUtils;
use Throwable;

/**
 * Convertor between java 1.12.2 ids and bedrocks current ids
 * Only intended for use in McEdit schematic file conversion
 */
class LegacyBlockIdConvertor
{
	/**
	 * @var array<int, int>
	 */
	private static array $conversionFrom;
	private static bool $available = false;

	public static function load(string $legacyBedrockSource): void
	{
		self::$conversionFrom = [];

		try {
			/** @var string $bedrockStringId */
			foreach (MixedUtils::getJsonData($legacyBedrockSource, 2) as $javaStringId => $bedrockStringId) {
				self::$conversionFrom[BlockParser::fromStringId($javaStringId)] = BlockParser::fromStringId($bedrockStringId);
			}
			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, McEdit schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
		}
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
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}