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
	private const MESSAGE_VERSION = "2.0.3";

	//TODO: All command strings should be translatable
	public const RESOURCE_WARNING = TextFormat::RED . "RESOURCE HEAVY" . TextFormat::RESET;

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
			$cMajor = explode(".", $current)[0];
			$gMajor = explode(".", self::MESSAGE_VERSION)[0];

			if ($cMajor === $gMajor) {
				//Updating the config while remaining current values
				$new = EasyEdit::getInstance()->getResource("messages.yml");
				if ($new === null || ($data = stream_get_contents($new)) === false) {
					throw new UnexpectedValueException("Couldn't read update data");
				}
				fclose($new);

				//Allow different line endings
				preg_match_all("/(.*)(?:\r\n|\n|\r|$)/", $data, $newConfig);
				$newConfig = $newConfig[1];

				//We can't just use yaml_parse as we want to preserve comments
				/** @var string $value */
				foreach ($messages->getAll() as $key => $value) {
					if ($key === "message-version") {
						continue;
					}
					$position = array_filter($newConfig, static function (string $line) use ($key): bool {
						return str_starts_with($line, $key . ":");
					});
					if (count($position) === 1) {
						$newConfig[key($position)] = $key . ': "' . str_replace(["\n", '"'], ["ARG_NEWLINE", '\"'], $value) . '"';
					}
				}

				file_put_contents($messages->getPath(), str_replace("ARG_NEWLINE", '\n', implode(PHP_EOL, $newConfig)));

				EasyEdit::getInstance()->getLogger()->notice("Your messages were updated to the newest Version");
			} else {
				//We can't update for major releases
				copy($messages->getPath(), $messages->getPath() . ".old");
				EasyEdit::getInstance()->saveResource("messages.yml", true);

				EasyEdit::getInstance()->getLogger()->warning("Your messages were replaced with a newer Version");
			}
			$messages->reload();
		}

		/** @var string[] $data */
		$data = $messages->getAll();
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