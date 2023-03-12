<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
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
	public const METADATA_BITS = 4;

	public static function load(): void
	{
		self::$conversionFrom = [];

		try {
			$version = RepoManager::getVersion();
			$conversionMap = [];
			/** @var string $bedrockState */
			foreach (RepoManager::getJson("legacy-conversion-map", 2) as $javaStringId => $bedrockState) {
				$javaId = explode(":", $javaStringId);
				$conversionMap[((int) $javaId[0]) << self::METADATA_BITS | ((int) $javaId[1])] = BlockParser::fromStateString($bedrockState, $version);
			}

			$result = BlockStateTranslationManager::requestRuntimeId($conversionMap, true);
			if ($result === false) {
				return;
			}
			self::$conversionFrom = $result;

			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, McEdit schematic conversion is not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
		}
	}

	/**
	 * @param int $id
	 * @return int
	 */
	public static function convertFromJava(int $id): int
	{
		if (isset(self::$conversionFrom[$id])) {
			return self::$conversionFrom[$id];
		}
		EditThread::getInstance()->debug("Failed to convert $id");
		return BlockParser::getInvalidBlockId();
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}