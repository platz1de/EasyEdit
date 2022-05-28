<?php

namespace platz1de\EasyEdit\utils;

use JsonException;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\plugin\DiskResourceProvider;
use pocketmine\utils\InternetException;
use UnexpectedValueException;

class RepoManager
{
	/**
	 * @var array<string, mixed>
	 */
	private static array $repoData;
	private static bool $available = false;

	public static function init(string $repo): void
	{
		if ($repo !== "") {
			try {
				self::$repoData = MixedUtils::getJsonData($repo, 4); //leave room for more complex structures later on
				self::$available = true;
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
			try {
				$url = self::$repoData[$file] ?? null;
				if (is_string($url)) {
					return MixedUtils::getJsonData($url, $depth);
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

		try {
			$parsed = json_decode($data, true, max(1, $depth), JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new InternetException("Invalid JSON: " . $e->getMessage());
		}

		if (!is_array($parsed)) {
			throw new InternetException("Loaded Data does not represent an array");
		}

		return $parsed;
	}
}