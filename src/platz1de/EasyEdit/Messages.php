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
	private const MESSAGE_VERSION = "2.0.8";

	/**
	 * @var string[]
	 */
	private static array $messages = [];

	public static function load(string $lang): void
	{
		if ($lang === "custom") {
			EasyEdit::getInstance()->saveResource("messages.yml");
			$custom = new Config(EasyEdit::getInstance()->getDataFolder() . "messages.yml", Config::YAML);

			$current = $custom->get("message-version", "1.0");
			if (!is_string($current)) {
				throw new UnexpectedValueException("message-version is not a string");
			}
			if ($current !== self::MESSAGE_VERSION) {
				copy($custom->getPath(), $custom->getPath() . ".old");
				EasyEdit::getInstance()->saveResource("messages.yml", true);

				EasyEdit::getInstance()->getLogger()->warning("Your messages were replaced with a newer Version");
				$custom->reload();
			}
			$messages = $custom->getAll();
		} else {
			if ($lang === "auto") {
				$lang = Server::getInstance()->getLanguage()->getLang();
			}
			$langData = EasyEdit::getInstance()->getResource("lang/$lang.yml");
			if ($langData === null || ($data = stream_get_contents($langData)) === false) {
				EasyEdit::getInstance()->getLogger()->error("Couldn't read language file $lang.yml, using fallback language");
				$fallback = EasyEdit::getInstance()->getResource("lang/eng.yml");
				if ($fallback === null || ($data = stream_get_contents($fallback)) === false) {
					throw new UnexpectedValueException("Couldn't read fallback language file eng.yml");
				}
				fclose($fallback);
			} else {
				fclose($langData);
			}
			$messages = yaml_parse($data);
			if (!is_array($messages)) {
				throw new UnexpectedValueException("Couldn't parse language file $lang.yml");
			}
		}
		foreach ($messages as $key => $value) {
			/** @var string|string[] $value */
			if (is_array($value)) {
				$messages[$key] = implode(PHP_EOL, $value);
			}
		}
		/** @var string[] $messages */
		self::$messages = $messages;

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