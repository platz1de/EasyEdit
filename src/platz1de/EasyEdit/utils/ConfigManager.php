<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\ItemConvertor;
use platz1de\EasyEdit\convert\LegacyBlockIdConvertor;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\listener\RemapEventListener;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\thread\input\ConfigInputData;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Config;
use UnexpectedValueException;

class ConfigManager
{
	private const CONFIG_VERSION = "2.1.0";

	/**
	 * @var int[]
	 */
	private static array $terrainIgnored = [];
	private static float $toolCooldown;
	private static bool $allowOtherHistory;
	private static bool $allowUnregisteredBlocks;
	private static int $fastSetMax;
	private static int $pathfindingMax;
	private static int $fillDistance;
	private static bool $sendDebug;
	private static bool $downloadData;
	private static bool $cacheData;
	private static string $dataRepo;

	private static string $resourcePath;
	private static string $cachePath;

	public static function load(): void
	{
		$config = self::loadConfig();

		Messages::load(strtolower(self::mustGetString($config, "language", "auto")));

		self::$terrainIgnored = array_map(static function (string $block): int {
			return BlockParser::getBlock($block) >> Block::INTERNAL_METADATA_BITS;
		}, self::mustGetStringArray($config, "terrain-ignored-blocks", []));

		self::$toolCooldown = self::mustGetFloat($config, "tool-cooldown", 0.5);

		self::$allowOtherHistory = self::mustGetBool($config, "allow-history-other", true);
		self::$allowUnregisteredBlocks = self::mustGetBool($config, "allow-unregistered-blocks", false);

		self::$fastSetMax = self::mustGetInt($config, "fast-set-max", 256000);
		self::$pathfindingMax = self::mustGetInt($config, "pathfinding-max", 1000000);
		self::$fillDistance = self::mustGetInt($config, "fill-distance", 200);

		if (self::mustGetBool($config, "remap-commands", false)) {
			RemapEventListener::init();
		}

		self::$sendDebug = self::mustGetBool($config, "send-debug", true);

		self::$downloadData = self::mustGetBool($config, "download-data", false);
		self::$cacheData = self::mustGetBool($config, "cache-data", false);
		self::$dataRepo = self::mustGetString($config, "data-repo", "");

		if (self::$cacheData && !is_dir(EasyEdit::getCachePath()) && !mkdir(EasyEdit::getCachePath(), 0777, true) && !is_dir(EasyEdit::getCachePath())) {
			throw new AssumptionFailedError("Failed to create cache directory");
		}

		ConfigInputData::create();
	}

	/**
	 * @param Config $config
	 * @param string $key
	 * @param bool   $default
	 * @return bool
	 */
	private static function mustGetBool(Config $config, string $key, bool $default): bool
	{
		$data = $config->get($key, $default);
		if (!is_bool($data)) {
			EasyEdit::getInstance()->getLogger()->warning("Your config value for " . $key . " is invalid, expected bool");
			return $default;
		}
		return $data;
	}

	/**
	 * @param Config $config
	 * @param string $key
	 * @param float  $default
	 * @return float
	 */
	private static function mustGetFloat(Config $config, string $key, float $default): float
	{
		$data = $config->get($key, $default);
		if (!is_float($data) && !is_int($data)) { //also accept ints
			EasyEdit::getInstance()->getLogger()->warning("Your config value for " . $key . " is invalid, expected float");
			return $default;
		}
		return $data;
	}

	/**
	 * @param Config $config
	 * @param string $key
	 * @param int    $default
	 * @return int
	 */
	private static function mustGetInt(Config $config, string $key, int $default): int
	{
		$data = $config->get($key, $default);
		if (!is_int($data)) {
			EasyEdit::getInstance()->getLogger()->warning("Your config value for " . $key . " is invalid, expected integer");
			return $default;
		}
		return $data;
	}

	/**
	 * @param Config $config
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	private static function mustGetString(Config $config, string $key, string $default): string
	{
		$data = $config->get($key, $default);
		if (!is_string($data)) {
			EasyEdit::getInstance()->getLogger()->warning("Your config value for " . $key . " is invalid, expected string");
			return $default;
		}
		return $data;
	}

	/**
	 * @param Config   $config
	 * @param string   $key
	 * @param string[] $default
	 * @return string[]
	 */
	private static function mustGetStringArray(Config $config, string $key, array $default): array
	{
		$data = $config->get($key, $default);
		if (!is_array($data) || array_filter($data, 'is_string') !== $data) {
			EasyEdit::getInstance()->getLogger()->warning("Your config value for " . $key . " is invalid, expected string array");
			return $default;
		}
		return $data;
	}

	/**
	 * @return int[]
	 */
	public static function getTerrainIgnored(): array
	{
		return self::$terrainIgnored;
	}

	/**
	 * @return bool
	 */
	public static function isAllowingOtherHistory(): bool
	{
		return self::$allowOtherHistory;
	}

	/**
	 * @return bool
	 */
	public static function isAllowingUnregisteredBlocks(): bool
	{
		return self::$allowUnregisteredBlocks;
	}

	public static function getFastSetMax(): int
	{
		return self::$fastSetMax;
	}

	public static function getPathfindingMax(): int
	{
		return self::$fastSetMax;
	}

	public static function getFillDistance(): int
	{
		return self::$fillDistance;
	}

	/**
	 * @return bool
	 */
	public static function isSendingDebug(): bool
	{
		return self::$sendDebug ?? true;
	}

	/**
	 * @return float
	 */
	public static function getToolCooldown(): float
	{
		return self::$toolCooldown;
	}

	/**
	 * @return string
	 */
	public static function getResourcePath(): string
	{
		return self::$resourcePath;
	}

	/**
	 * @return bool
	 */
	public static function useCache(): bool
	{
		return self::$cacheData;
	}

	/**
	 * @return string
	 */
	public static function getCachePath(): string
	{
		return self::$cachePath;
	}

	public static function putRawData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count(self::$terrainIgnored));
		foreach (self::$terrainIgnored as $id) {
			$stream->putInt($id);
		}
		$stream->putBool(self::$allowUnregisteredBlocks);
		$stream->putInt(self::$fastSetMax);
		$stream->putInt(self::$pathfindingMax);
		$stream->putInt(self::$fillDistance);
		$stream->putString(self::$downloadData ? self::$dataRepo : "");
		$stream->putBool(self::$sendDebug);
		$stream->putBool(self::$cacheData);
		$stream->putString(EasyEdit::getResourcePath());
		$stream->putString(EasyEdit::getCachePath());
	}

	public static function parseRawData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			self::$terrainIgnored[] = $stream->getInt();
		}
		self::$allowUnregisteredBlocks = $stream->getBool();
		self::$fastSetMax = $stream->getInt();
		self::$pathfindingMax = $stream->getInt();
		self::$fillDistance = $stream->getInt();
		self::$dataRepo = $stream->getString();
		self::$sendDebug = $stream->getBool();
		self::$cacheData = $stream->getBool();
		self::$resourcePath = $stream->getString();
		self::$cachePath = $stream->getString();
	}

	public static function distributeData(): void
	{
		RepoManager::init(self::$dataRepo);
		HeightMapCache::setIgnore(self::$terrainIgnored);
		LegacyBlockIdConvertor::load();
		BlockStateConvertor::load();
		BlockRotationManipulator::load();
		ItemConvertor::load();
	}

	private static function loadConfig(): Config
	{
		$config = EasyEdit::getInstance()->getConfig();

		$current = $config->get("config-version", "1.0");
		if (!is_string($current)) {
			throw new UnexpectedValueException("config-version is not a string");
		}
		if ($current !== self::CONFIG_VERSION) {
			$cMajor = explode(".", $current)[0];
			$gMajor = explode(".", self::CONFIG_VERSION)[0];

			if ($cMajor === $gMajor) {
				//Updating the config while remaining current values
				$new = EasyEdit::getInstance()->getResource("config.yml");
				if ($new === null || ($data = stream_get_contents($new)) === false) {
					throw new UnexpectedValueException("Couldn't read update data");
				}
				fclose($new);

				if (($old = file_get_contents($config->getPath())) === false) {
					throw new UnexpectedValueException("Couldn't read current data");
				}

				//Allow different line endings
				$newConfig = preg_split("/\r\n|\n|\r/", $data);
				$oldConfig = preg_split("/\r\n|\n|\r/", $old);
				if ($newConfig === false || $oldConfig === false) {
					throw new AssumptionFailedError("Failed to split strings");
				}

				//We can't just use yaml_parse as we want to preserve comments
				foreach ($config->getAll() as $key => $value) {
					if ($key === "config-version") {
						continue;
					}
					$position = array_filter($newConfig, static function (string $line) use ($key): bool {
						return str_starts_with($line, $key . ":");
					});
					$oldPosition = array_filter($oldConfig, static function (string $line) use ($key): bool {
						return str_starts_with($line, $key . ":");
					});
					if (count($position) === 1) {
						$newConfig[key($position)] = $oldConfig[key($oldPosition)];
					}
				}

				file_put_contents($config->getPath(), implode(PHP_EOL, $newConfig));

				EasyEdit::getInstance()->getLogger()->notice("Your config was updated to the newest Version");
			} else {
				//We can't update for major releases
				copy($config->getPath(), $config->getPath() . ".old");
				unlink($config->getPath());
				EasyEdit::getInstance()->saveDefaultConfig();

				EasyEdit::getInstance()->getLogger()->warning("Your config was replaced with a newer Version");
			}

			$config->reload();
		}
		return $config;
	}
}