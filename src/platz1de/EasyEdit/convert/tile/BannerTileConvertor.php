<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\block\tile\Banner;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\CompoundTag;

class BannerTileConvertor extends ColoredTileConvertor
{
	private const JAVA_UNSUPPORTED_CUSTOM_NAME = "CustomName";

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		try {
			return CompoundTag::create()
				->setInt(Banner::TAG_BASE, DyeColorIdMap::getInstance()->toInvertedId($this->parseColor($state)));
		} catch (UnsupportedBlockStateException) {
			return null;
		}
	}

	public function toBedrock(CompoundTag $tile): void
	{
		parent::toBedrock($tile);
		//TODO: apparently there is a integer to indicate the banner type (ominous / normal), not supported by pmmp though
		$tile->removeTag(self::JAVA_UNSUPPORTED_CUSTOM_NAME);
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		try {
			return $this->putInvertedColor($state, $tile->getInt(Banner::TAG_BASE, -1));
		} catch (UnsupportedBlockStateException) {
			return null;
		}
	}
}