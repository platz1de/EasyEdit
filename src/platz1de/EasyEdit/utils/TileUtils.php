<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

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

		//chest relation
		if ($compoundTag->getTag(Chest::TAG_PAIRX) instanceof IntTag && $compoundTag->getTag(Chest::TAG_PAIRZ) instanceof IntTag) {
			$compoundTag->setInt(Chest::TAG_PAIRX, $compoundTag->getInt(Chest::TAG_PAIRX) + $offset->getFloorX());
			$compoundTag->setInt(Chest::TAG_PAIRZ, $compoundTag->getInt(Chest::TAG_PAIRZ) + $offset->getFloorZ());
		}

		return $compoundTag;
	}
}