<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\block\tile\Bed;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\CompoundTag;

class BedTileConvertor extends ColoredTileConvertor
{
	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		try {
			return CompoundTag::create()
				->setInt(Bed::TAG_COLOR, DyeColorIdMap::getInstance()->toId($this->parseColor($state)));
		} catch (UnsupportedBlockStateException) {
			return null;
		}
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		return $this->putNormalColor($state, $tile->getInt(Bed::TAG_COLOR, -1));
	}
}