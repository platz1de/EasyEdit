<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Tile;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

class TileUtils
{
	/**
	 * @param CompoundTag|null $compoundTag
	 * @param int              $x
	 * @param int              $y
	 * @param int              $z
	 * @return CompoundTag|null
	 */
	public static function offsetCompound(?CompoundTag $compoundTag, int $x, int $y, int $z): ?CompoundTag
	{
		if ($compoundTag === null) {
			return null;
		}
		$compoundTag = clone $compoundTag;
		$compoundTag->setInt(Tile::TAG_X, $compoundTag->getInt(Tile::TAG_X) + $x);
		$compoundTag->setInt(Tile::TAG_Y, $compoundTag->getInt(Tile::TAG_Y) + $y);
		$compoundTag->setInt(Tile::TAG_Z, $compoundTag->getInt(Tile::TAG_Z) + $z);

		//chest relation
		if ($compoundTag->getTag(Chest::TAG_PAIRX) instanceof IntTag && $compoundTag->getTag(Chest::TAG_PAIRZ) instanceof IntTag) {
			$compoundTag->setInt(Chest::TAG_PAIRX, $compoundTag->getInt(Chest::TAG_PAIRX) + $x);
			$compoundTag->setInt(Chest::TAG_PAIRZ, $compoundTag->getInt(Chest::TAG_PAIRZ) + $z);
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
		//TODO: keep item order
		if ($compoundTag->getTag(Chest::TAG_PAIRX) instanceof IntTag && $compoundTag->getTag(Chest::TAG_PAIRZ) instanceof IntTag) {
			$prevPairX = $compoundTag->getInt(Chest::TAG_PAIRX);
			$compoundTag->setInt(Chest::TAG_PAIRX, $maxX - $compoundTag->getInt(Chest::TAG_PAIRZ));
			$compoundTag->setInt(Chest::TAG_PAIRZ, $prevPairX);
		}

		return $compoundTag;
	}

	/**
	 * @param int         $axis
	 * @param CompoundTag $compoundTag
	 * @param int         $maxX
	 * @return CompoundTag
	 */
	public static function flipCompound(int $axis, CompoundTag $compoundTag, int $maxX): CompoundTag
	{
		$compoundTag = clone $compoundTag;
		switch ($axis) {
			case Axis::X:
				$compoundTag->setInt(Tile::TAG_X, $maxX - $compoundTag->getInt(Tile::TAG_X));
				break;
			case Axis::Z:
				$compoundTag->setInt(Tile::TAG_Z, $maxX - $compoundTag->getInt(Tile::TAG_Z));
				break;
			case Axis::Y:
				$compoundTag->setInt(Tile::TAG_Y, $maxX - $compoundTag->getInt(Tile::TAG_Y));
				break;
		}

		//chest relation
		//TODO: keep item order
		if ($compoundTag->getTag(Chest::TAG_PAIRX) instanceof IntTag && $compoundTag->getTag(Chest::TAG_PAIRZ) instanceof IntTag) {
			switch ($axis) {
				case Axis::X:
					$compoundTag->setInt(Chest::TAG_PAIRX, $maxX - $compoundTag->getInt(Chest::TAG_PAIRX));
					break;
				case Axis::Z:
					$compoundTag->setInt(Chest::TAG_PAIRZ, $maxX - $compoundTag->getInt(Chest::TAG_PAIRZ));
					break;
			}
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