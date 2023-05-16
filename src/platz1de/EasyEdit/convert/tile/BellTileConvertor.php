<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\block\tile\Bell;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

class BellTileConvertor extends TileConvertorPiece
{
	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		return null;
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		$tile->removeTag(Bell::TAG_DIRECTION, Bell::TAG_RINGING, Bell::TAG_TICKS);
		return null;
	}
}