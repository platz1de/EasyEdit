<?php

namespace platz1de\EasyEdit\convert\tile;

use JsonException;
use pocketmine\block\tile\Sign;
use pocketmine\nbt\tag\CompoundTag;
use UnexpectedValueException;

class SignConvertor extends TileConvertorPiece
{
	public static function toBedrock($tile): void
	{
		//TODO: glowing & color
		for ($i = 1; $i <= 4; $i++) {
			$line = $tile->getString("Text" . $i);
			try {
				/** @var string[] $json */
				$json = json_decode($line, true, 2, JSON_THROW_ON_ERROR);
				if (!isset($json["text"])) {
					throw new JsonException("Missing text key");
				}
				$text = $json["text"];
			} catch (JsonException) {
				throw new UnexpectedValueException("Invalid JSON in sign text: " . $line);
			}
			$tile->setString("Text" . $i, $text);
		}
	}

	public static function toJava(int $blockId, CompoundTag $tile): void
	{
		//TODO: glowing & color
		for ($i = 1; $i <= 4; $i++) {
			$line = $tile->getString("Text" . $i);
			try {
				/** @var string $json */
				$json = json_encode(["text" => $line], JSON_THROW_ON_ERROR);
			} catch (JsonException) {
				throw new UnexpectedValueException("Failed to encode JSON for sign text: " . $line);
			}
			$tile->setString("Text" . $i, $json);
			$tile->removeTag(Sign::TAG_TEXT_BLOB);
		}
	}
}