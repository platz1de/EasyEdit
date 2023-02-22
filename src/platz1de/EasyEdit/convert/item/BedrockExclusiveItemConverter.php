<?php

namespace platz1de\EasyEdit\convert\item;

use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\nbt\tag\CompoundTag;

/**
 * Moved bedrock exclusive tags into the "tag" tag, as it allows custom data
 */
class BedrockExclusiveItemConverter extends ItemConvertorPiece
{
	public function toBedrock(CompoundTag $item, CompoundTag $tag): void
	{
		$this->pullBedrock(SavedItemStackData::TAG_WAS_PICKED_UP, $item, $tag);
	}

	public function toJava(CompoundTag $item, CompoundTag $tag): void
	{
		$this->pushBedrock(SavedItemStackData::TAG_WAS_PICKED_UP, $item, $tag);
	}

	private function pushBedrock(string $name, CompoundTag $item, CompoundTag $dataTag): void
	{
		$tag = $item->getTag($name);
		if ($tag !== null) {
			$dataTag->setTag("Bedrock_" . $name, $tag);
			$item->removeTag($name);
		}
	}

	private function pullBedrock(string $name, CompoundTag $item, CompoundTag $dataTag): void
	{
		$tag = $dataTag->getTag("Bedrock_" . $name);
		if ($tag !== null) {
			$item->setTag($name, $tag);
			$dataTag->removeTag("Bedrock_" . $name);
		}
	}
}