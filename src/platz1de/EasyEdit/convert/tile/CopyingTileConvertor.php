<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

class CopyingTileConvertor extends TileConvertorPiece
{
	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		return null;
	}

	public function toBedrock(CompoundTag $tile): void { }

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		return null;
	}
}