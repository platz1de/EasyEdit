<?php

namespace platz1de\EasyEdit\utils;

use JsonException;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;

class MixedUtils
{
	/**
	 * @param int  $int
	 * @param bool $data
	 * @return string
	 */
	public static function humanReadable(int $int, bool $data = false): string
	{
		$factor = $data ? floor(log($int, 1024)) : floor((strlen((string) $int) - 1) / 3);
		$string = $data ? number_format($int / 1024 ** $factor) : number_format($int);
		$unit = $data ? ["B", "KB", "MB", "GB", "TB"] : ["", "K", "M", "G", "T"];
		return substr($string, 0, strpos($string, ",") < 3 ? 4 : 3) . $unit[$factor];
	}

	/**
	 * @param string $dir
	 */
	public static function deleteDir(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}

		$files = scandir($dir);
		if ($files === false) {
			return;
		}
		foreach (array_diff($files, ['.', '..']) as $file) {
			is_dir($dir . DIRECTORY_SEPARATOR . $file) ? self::deleteDir($dir . DIRECTORY_SEPARATOR . $file) : unlink($dir . DIRECTORY_SEPARATOR . $file);
		}
		rmdir($dir);
	}

	/**
	 * @param int $cooldown
	 * @return int previous cooldown
	 */
	public static function setAutoSave(int $cooldown): int
	{
		$previous = Server::getInstance()->getWorldManager()->getAutoSaveInterval();
		Server::getInstance()->getWorldManager()->setAutoSaveInterval($cooldown);
		return $previous;
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public static function downloadData(string $url): string
	{
		$data = Internet::getURL($url, 10, [], $err);
		if ($data === null || $data->getCode() !== 200) {
			if (isset($err)) {
				throw new InternetException($err);
			}
			throw new InternetException("Couldn't load file: " . $data?->getCode());
		}

		return $data->getBody();
	}

	/**
	 * @param string $json
	 * @param int    $depth
	 * @return array<string, mixed>
	 */
	public static function decodeJson(string $json, int $depth): array
	{
		try {
			$parsed = json_decode($json, true, max(1, $depth), JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new InternetException("Invalid JSON: " . $e->getMessage());
		}

		if (!is_array($parsed)) {
			throw new InternetException("Loaded Data does not represent an array");
		}

		return $parsed;
	}
}