<?php

namespace platz1de\EasyEdit\convert;

use JsonException;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use Throwable;
use UnexpectedValueException;

class ItemConvertor
{
	/**
	 * @var array<string, array{int, int}>
	 */
	private static array $itemTranslationBedrock;
	/**
	 * @var array<int, array<int, string>>
	 */
	private static array $itemTranslationJava;

	public static function load(): void
	{
		/** @var string $bedrock */
		foreach (RepoManager::getJson("bedrock-item-map", 2) as $java => $bedrock) {
			$id = explode(":", $bedrock);
			self::$itemTranslationBedrock[$java] = [(int) $id[0], (int) $id[1]];
			self::$itemTranslationJava[(int) $id[0]][(int) $id[1]] = $java;
		}
	}

	/**
	 * @param CompoundTag $item
	 */
	public static function convertItemBedrock(CompoundTag $item): void
	{
		try {
			$javaId = $item->getString("id");
		} catch (Throwable) {
			return; //probably already bedrock format, or at least not convertable
		}
		try {
			$i = self::$itemTranslationBedrock["minecraft:" . mb_strtolower(str_replace([" ", "minecraft:"], ["_", ""], trim($javaId)))];
		} catch (Throwable) {
			EditThread::getInstance()->debug("Couldn't convert item " . $javaId);
			return;
		}
		$item->setShort("id", $i[0]);
		$item->setShort("Damage", $i[1]);

		try {
			$extraData = $item->getCompoundTag("tag");
		} catch (Throwable) {
			return;
		}
		if ($extraData instanceof CompoundTag) {
			foreach ($extraData->getValue() as $key => $value) {
				switch ($key) {
					case "display":
						/** @var CompoundTag $value */
						$customName = $value->getString("Name", "");
						if ($customName !== "") {
							try {
								/** @var array<array{text: string}> $json */
								$json = json_decode($customName, true, 3, JSON_THROW_ON_ERROR);
								if (!isset($json[0]["text"])) {
									throw new JsonException("Missing text key");
								}
								$name = $json[0]["text"];
							} catch (JsonException) {
								throw new UnexpectedValueException("Invalid JSON for item name");
							}
							$value->setString("Name", $name);
						}

						try {
							$lore = $value->getListTag("Lore");
						} catch (Throwable) {
							break;
						}
						if ($lore === null) {
							break;
						}
						$lines = new ListTag([], NBT::TAG_String);
						/** @var StringTag $line */
						foreach ($lore as $line) {
							try {
								/** @var array<array{text: string}> $json */
								$json = json_decode($line->getValue(), true, 3, JSON_THROW_ON_ERROR);
								if (!isset($json[0]["text"])) {
									throw new JsonException("Missing text key");
								}
								$text = $json[0]["text"];
							} catch (JsonException) {
								throw new UnexpectedValueException("Invalid JSON for item lore");
							}
							$lines->push(new StringTag($text));
						}
						$value->setTag("Lore", $lines);
				}
			}
		}
	}

	/**
	 * @param CompoundTag $item
	 */
	public static function convertItemJava(CompoundTag $item): void
	{
		try {
			$i = self::$itemTranslationJava[$item->getShort("id")][$item->getShort("Damage")];
		} catch (Throwable) {
			EditThread::getInstance()->debug("Couldn't convert item " . $item->getShort("id") . ":" . $item->getShort("Damage"));
			return;
		}
		$item->removeTag("Damage");
		$item->setString("id", $i);

		try {
			$extraData = $item->getCompoundTag("tag");
		} catch (Throwable) {
			return;
		}
		if ($extraData instanceof CompoundTag) {
			foreach ($extraData->getValue() as $key => $value) {
				switch ($key) {
					case "display":
						/** @var CompoundTag $value */
						$customName = $value->getString("Name", "");
						if ($customName !== "") {
							try {
								/** @var string $json */
								$json = json_encode([["text" => $customName]], JSON_THROW_ON_ERROR);
							} catch (JsonException) {
								throw new UnexpectedValueException("Failed to encode JSON for item name");
							}
							$value->setString("Name", $json);
						}

						try {
							$lore = $value->getListTag("Lore");
						} catch (Throwable) {
							break;
						}
						if ($lore === null) {
							break;
						}
						$lines = new ListTag([], NBT::TAG_String);
						/** @var StringTag $line */
						foreach ($lore as $line) {
							$text = $line->getValue();
							try {
								/** @var string $json */
								$json = json_encode([["text" => $text]], JSON_THROW_ON_ERROR);
							} catch (JsonException) {
								throw new UnexpectedValueException("Failed to encode JSON for item lore");
							}
							$lines->push(new StringTag($json));
						}
						$value->setTag("Lore", $lines);
				}
			}
		}
	}
}