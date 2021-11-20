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

	/**
	 * @param CompoundTag $compoundTag
	 * @param int         $maxX
	 * @return CompoundTag
	 */
	public static function rotateCompound(CompoundTag $compoundTag, int $maxX): CompoundTag
	{
		$compoundTag = clone $compoundTag;
		$prevX = $compoundTag->getInt(Tile::TAG_X);
		$compoundTag->setInt(Tile::TAG_X, $maxX - $compoundTag->getInt(Tile::TAG_Z));
		$compoundTag->setInt(Tile::TAG_Z, $prevX);

		//chest relation
		if ($compoundTag->getTag(Chest::TAG_PAIRX) instanceof IntTag && $compoundTag->getTag(Chest::TAG_PAIRZ) instanceof IntTag) {
			$prevPairX = $compoundTag->getInt(Chest::TAG_PAIRX);
			$compoundTag->setInt(Chest::TAG_PAIRX, $maxX - $compoundTag->getInt(Chest::TAG_PAIRZ));
			$compoundTag->setInt(Chest::TAG_PAIRZ, $prevPairX);
		}

		return $compoundTag;
	}

	/**
	 * @param CompoundTag $compoundTag
	 * @param Vector3     $min
	 * @param Vector3     $max
	 * @return bool
	 */
	public static function isBetweenVectors(CompoundTag $compoundTag, Vector3 $min, Vector3 $max): bool
	{
		$x = $compoundTag->getInt(Tile::TAG_X);
		$z = $compoundTag->getInt(Tile::TAG_Z);
		return $x >= $min->getFloorX() && $x <= $max->getFloorX() && $z >= $min->getFloorZ() && $z <= $max->getFloorZ();
	}
}