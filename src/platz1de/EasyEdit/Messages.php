<?php

namespace platz1de\EasyEdit;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

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
	 */
	public static function send($players, string $id, $replace = []): void
	{
		if (is_array($players)) {
			foreach ($players as $player) {
				if ($player instanceof Player || ($player = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
					$player->sendMessage(self::translate("prefix") . self::replace($id, $replace));
				}
			}
		} else {
			self::send([$players], $id, $replace);
		}
	}

	/**
	 * @param string          $id
	 * @param string|string[] $replace
	 * @return string
	 */
	public static function replace(string $id, $replace = []): string
	{
		if (is_array($replace)) {
			return str_replace(array_keys($replace), array_values($replace), self::translate($id));
		}
		return str_replace("{player}", $replace, self::translate($id));
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function translate(string $id): string
	{
		return self::$messages[$id] ?? $id;
	}
}