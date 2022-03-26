<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\thread\input\MessageInputData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;

class Messages
{
	private const MESSAGE_VERSION = "2.0.5";

	/**
	 * @var string[]
	 */
	private static array $messages = [];

	public static function load(): void
	{
		EasyEdit::getInstance()->saveResource("messages.yml");
		$messages = new Config(EasyEdit::getInstance()->getDataFolder() . "messages.yml", Config::YAML);

		$current = $messages->get("message-version", "1.0");
		if (!is_string($current)) {
			throw new UnexpectedValueException("message-version is not a string");
		}
		if ($current !== self::MESSAGE_VERSION) {
			copy($messages->getPath(), $messages->getPath() . ".old");
			EasyEdit::getInstance()->saveResource("messages.yml", true);

			EasyEdit::getInstance()->getLogger()->warning("Your messages were replaced with a newer Version");
			$messages->reload();
		}

		$data = $messages->getAll();
		foreach ($data as $key => $value) {
			/** @var string|array $value */
			if (is_array($value)) {
				$data[$key] = implode(PHP_EOL, $value);
			}
		}
		/** @var string[] $data */
		self::$messages = $data;

		MessageInputData::from(self::$messages);
	}

	/**
	 * @param string|string[]|Player|Player[] $players
	 * @param string                          $id
	 * @param string|string[]                 $replace
	 * @param bool                            $isId
	 * @param bool                            $usePrefix
	 */
	public static function send(mixed $players, string $id, mixed $replace = [], bool $isId = true, bool $usePrefix = true): void
	{
		if (is_array($players)) {
			foreach ($players as $player) {
				if ($player instanceof Player || ($player = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
					$player->sendMessage(($usePrefix ? self::translate("prefix") : "") . self::replace($id, $replace, $isId));
				}
			}
		} else {
			self::send([$players], $id, $replace, $isId, $usePrefix);
		}
	}

	/**
	 * @param string          $id
	 * @param string|string[] $replace
	 * @param bool            $isId
	 * @return string
	 */
	public static function replace(string $id, mixed $replace = [], bool $isId = true): string
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

	/**
	 * @param string[] $messages
	 */
	public static function setMessageData(array $messages): void
	{
		self::$messages = $messages;
	}
}