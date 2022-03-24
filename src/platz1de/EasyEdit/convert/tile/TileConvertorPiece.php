<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\nbt\tag\CompoundTag;

abstract class TileConvertorPiece
{
	abstract public static function toBedrock($tile): void;

	abstract public static function toJava(int $blockId, CompoundTag $tile): void;
}