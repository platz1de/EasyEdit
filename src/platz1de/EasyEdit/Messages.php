<?php

namespace platz1de\EasyEdit;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Messages
{
	private static $messages = [];

	public static function load(): void
	{
		EasyEdit::getInstance()->saveResource("messages.yml");
		self::$messages = (new Config(EasyEdit::getInstance()->getDataFolder() . "messages.yml", Config::YAML))->getAll();
	}

	/**
	 * @param string|string[]|Player|Player[] $players
	 * @param string                          $id
	 * @param string|string[]                 $replace
	 * @param bool                            $isId
	 * @param bool                            $usePrefix
	 */
	public static function send($players, string $id, $replace = [], bool $isId = true, bool $usePrefix = true): void
	{
		if (is_array($players)) {
			foreach ($players as $player) {
				if ($player instanceof Player || ($player = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
					$player->sendMessage(($usePrefix ? self::translate("prefix") : "") . self::replace($id, $replace, $isId));
				}
			}
		} else {
			self::send([$players], $id, $replace);
		}
	}

	/**
	 * @param string          $id
	 * @param string|string[] $replace
	 * @param bool            $isId
	 * @return string
	 */
	public static function replace(string $id, $replace = [], bool $isId = true): string
	{
		if (is_array($replace)) {
			return str_replace(array_keys($replace), array_values($replace), $isId ? self::translate($id) : $id);
		}
		return str_replace("{player}", $replace, $isId ? self::translate($id) : $id);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function translate(string $id): string
	{
		return self::$messages[$id] ?? TextFormat::RED . "The message " . $id . " was not found, please try deleting your messages.yml";
	}
}