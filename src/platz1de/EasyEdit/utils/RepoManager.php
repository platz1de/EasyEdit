<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\thread\EditThread;
use pocketmine\plugin\DiskResourceProvider;
use pocketmine\utils\InternetException;
use UnexpectedValueException;

class RepoManager
{
	private const CACHE_PREFIX = "repo_cache_";

	/**
	 * @var array<string, mixed>
	 */
	private static array $repoData;
	private static bool $available = false;

	public static function init(string $repo): void
	{
		if ($repo !== "") {
			try {
				self::$repoData = MixedUtils::decodeJson(MixedUtils::downloadData($repo), 4); //leave room for more complex structures later on
				self::$available = true;
				if (ConfigManager::useCache()) {
					$version = self::$repoData["version"];
					$cache = scandir(ConfigManager::getCachePath());
					if ($cache !== false) {
						foreach (array_diff($cache, ['.', '..']) as $file) {
							//only delete files that are known to us
							if (str_starts_with($file, self::CACHE_PREFIX) && !str_ends_with($file, "_" . $version . ".json")) {
								EditThread::getInstance()->getLogger()->debug("Deleting old cache file " . $file);
								unlink(ConfigManager::getCachePath() . $file);
							}
						}
					}
				}
			} catch (InternetException $e) {
				EditThread::getInstance()->getLogger()->error("Failed to load repo data: " . $e->getMessage());
			}
		}
	}

	/**
	 * @param string $file
	 * @param int    $depth
	 * @return array<string, mixed>
	 */
	public static function getJson(string $file, int $depth): array
	{
		if (self::$available) {
			if (ConfigManager::useCache()) {
				$cache = self::CACHE_PREFIX . $file . "_" . self::$repoData["version"] . ".json";
				if (is_file(ConfigManager::getCachePath() . $cache)) {
					try {
						return MixedUtils::decodeJson((string) file_get_contents(ConfigManager::getCachePath() . $cache), $depth);
					} catch (InternetException $e) {
						EditThread::getInstance()->getLogger()->warning("Failed to read cache " . $cache . ": " . $e->getMessage());
						unlink(ConfigManager::getCachePath() . $cache);
					}
				}
			}
			try {
				$url = self::$repoData[$file] ?? null;
				if (is_string($url)) {
					$data = MixedUtils::downloadData($url);
					if (ConfigManager::useCache()) {
						EditThread::getInstance()->getLogger()->debug("Caching " . $file . " to " . ConfigManager::getCachePath() . self::CACHE_PREFIX . $file . "_" . self::$repoData["version"] . ".json");
						file_put_contents(ConfigManager::getCachePath() . self::CACHE_PREFIX . $file . "_" . self::$repoData["version"] . ".json", $data);
					}
					return MixedUtils::decodeJson($data, $depth);
				}
				EditThread::getInstance()->getLogger()->error("Repo data does not contain $file");
			} catch (InternetException $e) {
				EditThread::getInstance()->getLogger()->warning("Failed to download datafile " . $file . ": " . $e->getMessage());
			}
		}

		$stream = (new DiskResourceProvider(ConfigManager::getResourcePath()))->getResource("repo/" . $file . ".json");
		if ($stream === null || ($data = stream_get_contents($stream)) === false) {
			throw new UnexpectedValueException("Couldn't read fallback datafile " . $file);
		}
		fclose($stream);

		return MixedUtils::decodeJson($data, $depth);
	}
}