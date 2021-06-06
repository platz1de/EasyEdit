<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\EasyEdit;
use pocketmine\level\Level;
use pocketmine\Server;

class MixedUtils
{
	/**
	 * @param string $int
	 * @param bool   $data
	 * @return string
	 */
	public static function humanReadable(string $int, bool $data = false): string
	{
		$factor = $data ? floor(log($int, 1024)) : floor((strlen($int) - 1) / 3);
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

		$files = array_diff(scandir($dir), ['.', '..']);
		foreach ($files as $file) {
			is_dir($dir . DIRECTORY_SEPARATOR . $file) ? self::deleteDir($dir . DIRECTORY_SEPARATOR . $file) : unlink($dir . DIRECTORY_SEPARATOR . $file);
		}
		rmdir($dir);
	}

	/**
	 * @return bool
	 */
	public static function pauseAutoSave(): bool
	{
		//only disable server auto saving
		if (!Server::getInstance()->getAutoSave()) {
			return false;
		}

		$data = [];
		foreach (Server::getInstance()->getLevels() as $level) {
			$data[$level->getFolderName()] = $level->getAutoSave();
		}

		Server::getInstance()->setAutoSave(false);

		//other plugins could disable auto-saving in set worlds only
		foreach (Server::getInstance()->getLevels() as $level) {
			$level->setAutoSave($data[$level->getFolderName()]);
		}
		return true;
	}

	public static function continueAutoSave(): void
	{
		if (Server::getInstance()->getAutoSave()) {
			EasyEdit::getInstance()->getLogger()->critical("AutoSave was activated by unknown source");
		}

		$data = [];
		foreach (Server::getInstance()->getLevels() as $level) {
			$data[$level->getFolderName()] = $level->getAutoSave();
		}

		Server::getInstance()->setAutoSave(true);

		foreach (Server::getInstance()->getLevels() as $level) {
			$level->setAutoSave($data[$level->getFolderName()]);
		}
	}
}