<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\convert\item\BedrockExclusiveItemConverter;
use platz1de\EasyEdit\convert\item\BlockItemConvertor;
use platz1de\EasyEdit\convert\item\ItemConvertorPiece;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\nbt\tag\CompoundTag;
use Throwable;

class ItemConvertor
{
	/**
	 * @var array<string, array{string, int}>
	 */
	private static array $itemTranslationBedrock = [];
	/**
	 * @var array<string, array<int, string>>
	 */
	private static array $itemTranslationJava = [];
	/**
	 * @var ItemConvertorPiece[]
	 */
	private static array $convertors = [];

	public static function load(): void
	{
		try {
			/**
			 * @var string                                  $java
			 * @var array{name: string, damage: string|int} $bedrock
			 */
			foreach (RepoManager::getJson("item-conversion-map", 2) as $java => $bedrock) {
				self::$itemTranslationBedrock[$java] = [$bedrock["name"], (int) $bedrock["damage"]];
				self::$itemTranslationJava[$bedrock["name"]][(int) $bedrock["damage"]] = $java;
			}

			self::$convertors = [
				new BlockItemConvertor(),
				new BedrockExclusiveItemConverter()
			];
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, Item conversion is not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
		}
	}

	/**
	 * @param CompoundTag $item
	 */
	public static function convertItemBedrock(CompoundTag $item): ?CompoundTag
	{
		try {
			$javaId = $item->getString("id");
		} catch (Throwable) {
			$real = self::convertItemJava($item);
			if ($real === null) {
				return null; //couldn't convert
			}
			return $item; //already in bedrock format
		}
		try {
			$i = self::$itemTranslationBedrock["minecraft:" . mb_strtolower(str_replace([" ", "minecraft:"], ["_", ""], trim($javaId)))];
		} catch (Throwable) {
			EditThread::getInstance()->debug("Couldn't convert item " . $javaId);
			return null;
		}
		$item->removeTag("id");
		$item->setString(SavedItemData::TAG_NAME, $i[0]);
		$item->setShort(SavedItemData::TAG_DAMAGE, $i[1]);

		try {
			$extraData = $item->getCompoundTag(SavedItemData::TAG_TAG);
			$item->removeTag(SavedItemData::TAG_TAG);
			if (!$extraData instanceof CompoundTag) {
				$extraData = new CompoundTag();
			}
		} catch (Throwable) {
			$extraData = new CompoundTag();
		}
		foreach (self::$convertors as $convertor) {
			$convertor->toBedrock($item, $extraData);
		}
		if ($extraData->getCount() > 0) {
			$item->setTag(SavedItemData::TAG_TAG, $extraData);
		}
		return $item;
	}

	/**
	 * @param CompoundTag $item
	 */
	public static function convertItemJava(CompoundTag $item): ?CompoundTag
	{
		try {
			$i = self::$itemTranslationJava[$item->getString(SavedItemData::TAG_NAME)][$item->getShort(SavedItemData::TAG_DAMAGE)];
		} catch (Throwable) {
			EditThread::getInstance()->debug("Couldn't convert item " . $item->getString(SavedItemData::TAG_NAME) . ":" . $item->getShort(SavedItemData::TAG_DAMAGE));
			return null;
		}
		$item->removeTag(SavedItemData::TAG_NAME);
		$item->removeTag(SavedItemData::TAG_DAMAGE);
		$item->setString("id", $i);

		try {
			$extraData = $item->getCompoundTag(SavedItemData::TAG_TAG);
			$item->removeTag(SavedItemData::TAG_TAG);
			if (!$extraData instanceof CompoundTag) {
				$extraData = new CompoundTag();
			}
		} catch (Throwable) {
			$extraData = new CompoundTag();
		}
		foreach (self::$convertors as $convertor) {
			$convertor->toJava($item, $extraData);
		}
		if ($extraData->getCount() > 0) {
			$item->setTag(SavedItemData::TAG_TAG, $extraData);
		}
		return $item;
	}
}