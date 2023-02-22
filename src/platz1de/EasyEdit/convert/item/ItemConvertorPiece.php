<?php

namespace platz1de\EasyEdit\convert\item;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

abstract class ItemConvertorPiece
{
	abstract public function toBedrock(CompoundTag $item, CompoundTag $tag): void;

	abstract public function toJava(CompoundTag $item, CompoundTag $tag): void;
}