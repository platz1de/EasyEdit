<?php

namespace platz1de\EasyEdit\utils;

use InvalidArgumentException;
use pocketmine\entity\Location;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class VectorUtils
{
	/**
	 * @param Location $from
	 * @return int
	 */
	public static function getFacing(Location $from): int
	{
		$yaw = $from->getYaw();
		$pitch = $from->getPitch();
		if ($pitch >= 45) {
			return Facing::DOWN;
		}
		if ($pitch <= -45) {
			return Facing::UP;
		}
		if ($yaw >= 315 || $yaw < 45) {
			return Facing::SOUTH;
		}
		if ($yaw < 135) {
			return Facing::WEST;
		}
		if ($yaw < 225) {
			return Facing::NORTH;
		}
		return Facing::EAST;
	}

	/**
	 * @param Vector3 $a
	 * @param Vector3 $b
	 * @return Vector3
	 */
	public static function multiply(Vector3 $a, Vector3 $b): Vector3
	{
		return new Vector3($a->getX() * $b->getX(), $a->getY() * $b->getY(), $a->getZ() * $b->getZ());
	}

	/**
	 * @param Vector3 $vector
	 * @return float
	 */
	public static function product(Vector3 $vector): float
	{
		return $vector->getX() * $vector->getY() * $vector->getZ();
	}

	/**
	 * @param Vector3 $vector
	 * @return Vector3
	 */
	public static function enforceHeight(Vector3 $vector): Vector3
	{
		return new Vector3($vector->getX(), min(World::Y_MAX - 1, max(0, $vector->getY())), $vector->getZ());
	}

	public static function isVectorInBoundaries(Vector3 $vector, Vector3 $min, Vector3 $max): bool
	{
		return $vector->getX() >= $min->getX() && $vector->getX() <= $max->getX() && $vector->getY() >= $min->getY() && $vector->getY() <= $max->getY() && $vector->getZ() >= $min->getZ() && $vector->getZ() <= $max->getZ();
	}

	/**
	 * @param int     $x
	 * @param int     $y
	 * @param int     $z
	 * @param Vector3 $min
	 * @param Vector3 $max
	 */
	public static function adjustBoundaries(int $x, int $y, int $z, Vector3 $min, Vector3 $max): void
	{
		if ($x < $min->getX()) {
			$min->x = $x;
		} elseif ($x > $max->getX()) {
			$max->x = $x;
		}
		if ($y < $min->getY()) {
			$min->y = $y;
		} elseif ($y > $max->getY()) {
			$max->y = $y;
		}
		if ($z < $min->getZ()) {
			$min->z = $z;
		} elseif ($z > $max->getZ()) {
			$max->z = $z;
		}
	}

	/**
	 * @param Vector3 $vector
	 * @param int     $axis
	 * @return float|int
	 */
	public static function getVectorAxis(Vector3 $vector, int $axis): float|int
	{
		return match ($axis) {
			Axis::X => $vector->getX(),
			Axis::Y => $vector->getY(),
			Axis::Z => $vector->getZ(),
			default => throw new InvalidArgumentException("Invalid axis $axis"),
		};
	}
}