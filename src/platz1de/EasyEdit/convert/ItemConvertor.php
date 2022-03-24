<?php

namespace platz1de\EasyEdit\convert;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\VanillaItems;
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
		/** @var string $blockId */
		foreach (VanillaBlocks::getAll() as $blockId => $block) {
			$item = $block->asItem();
			self::$itemTranslationBedrock[$blockId] = [$item->getId(), $item->getMeta()];
			self::$itemTranslationJava[$item->getId()][$item->getMeta()] = "minecraft:" . mb_strtolower($blockId);
		}
		/** @var string $itemId */
		foreach (VanillaItems::getAll() as $itemId => $item) {
			self::$itemTranslationBedrock[$itemId] = [$item->getId(), $item->getMeta()];
			self::$itemTranslationJava[$item->getId()][$item->getMeta()] = "minecraft:" . mb_strtolower($itemId);
		}
		foreach (LegacyStringToItemParser::getInstance()->getMappings() as $key => $id) {
			if (!is_numeric($key) && !isset(self::$itemTranslationJava[$id])) {
				self::$itemTranslationBedrock[mb_strtoupper($key)] = [$id, 0];
				self::$itemTranslationJava[$id][0] = "minecraft:" . mb_strtolower($key);
			}
		}
	}

	/**
	 * @param string $item
	 * @return array{int, int}
	 */
	public static function convertItemBedrock(string $item): array
	{
		//TODO: special data (lore...)
		try {
			return self::$itemTranslationBedrock[mb_strtoupper(str_replace([" ", "minecraft:"], ["_", ""], trim($item)))];
		} catch (Throwable) {
			throw new UnexpectedValueException("Couldn't convert item " . $item);
		}
	}

	/**
	 * @param int $id
	 * @param int $meta
	 * @return string
	 */
	public static function convertItemJava(int $id, int $meta): string
	{
		try {
			return self::$itemTranslationJava[$id][$meta];
		} catch (Throwable) {
			throw new UnexpectedValueException("Couldn't convert item " . $id . ":" . $meta);
		}
	}
}