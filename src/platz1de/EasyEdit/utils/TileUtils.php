<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;

class TileUtils
{
	/**
	 * @param CompoundTag $compoundTag
	 * @param Vector3     $offset
	 * @return CompoundTag
	 */
	public static function offsetCompound(CompoundTag $compoundTag, Vector3 $offset): CompoundTag
	{
		$compoundTag = clone $compoundTag;
		$compoundTag->setInt(Tile::TAG_X, $compoundTag->getInt(Tile::TAG_X) + $offset->getFloorX());
		$compoundTag->setInt(Tile::TAG_Y, $compoundTag->getInt(Tile::TAG_Y) + $offset->getFloorY());
		$compoundTag->setInt(Tile::TAG_Z, $compoundTag->getInt(Tile::TAG_Z) + $offset->getFloorZ());
		return $compoundTag;
	}

	/**
	 * @param Chunk $chunk
	 * @return CompoundTag[]
	 */
	public static function loadFrom(Chunk $chunk): array
	{
		return (function (): array {
			return $this->NBTtiles;
		})->call($chunk);
	}
}