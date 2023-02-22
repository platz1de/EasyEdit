<?php

namespace platz1de\EasyEdit\convert\item;

use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\nbt\tag\CompoundTag;

/**
 * Moved bedrock exclusive tags into the "tag" tag, as it allows custom data
 */
class BedrockExclusiveItemConvertor extends ItemConvertorPiece
{
	public function toBedrock(CompoundTag $item, CompoundTag $tag): void
	{
		$tag->removeTag("CustomModelData", "AttributeModifiers");

		$wpuTag = $item->getTag("Bedrock_" . SavedItemStackData::TAG_WAS_PICKED_UP);
		if ($wpuTag !== null) {
			$item->setTag(SavedItemStackData::TAG_WAS_PICKED_UP, $wpuTag);
			$tag->removeTag("Bedrock_" . SavedItemStackData::TAG_WAS_PICKED_UP);
		}
	}

	public function toJava(CompoundTag $item, CompoundTag $tag): void
	{
		$wpuTag = $item->getTag(SavedItemStackData::TAG_WAS_PICKED_UP);
		if ($wpuTag !== null) {
			$tag->setTag("Bedrock_" . SavedItemStackData::TAG_WAS_PICKED_UP, $wpuTag);
			$item->removeTag(SavedItemStackData::TAG_WAS_PICKED_UP);
		}
	}
}