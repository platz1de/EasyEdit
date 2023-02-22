<?php

namespace platz1de\EasyEdit\convert\item;

use pocketmine\nbt\tag\CompoundTag;

/**
 * pmmp currently saves these the java way, so we don't need to move them
 */
class AdventurePropertyItemConvertor extends ItemConvertorPiece
{
	public function toBedrock(CompoundTag $item, CompoundTag $tag): void
	{
		//TODO: check if pmmp can handle all blocks (probably not, as it can't really handle java properties)
	}

	public function toJava(CompoundTag $item, CompoundTag $tag): void
	{
		// TODO
	}
}