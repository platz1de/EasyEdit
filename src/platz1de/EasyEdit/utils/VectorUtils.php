<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class VectorUtils
{
	/**
	 * @param Location $from
	 * @param Vector3  $vector
	 * @param int      $amount
	 * @return Vector3
	 */
	public static function moveVectorInSight(Location $from, Vector3 $vector, int $amount = 1): Vector3
	{
		$yaw = $from->getYaw();
		$pitch = $from->getPitch();
		if ($pitch >= 45) {
			$p = $vector->down($amount);
		} elseif ($pitch <= -45) {
			$p = $vector->up($amount);
		} elseif ($yaw >= 315 || $yaw < 45) {
			$p = $vector->south($amount);
		} elseif ($yaw < 135) {
			$p = $vector->west($amount);
		} elseif ($yaw < 225) {
			$p = $vector->north($amount);
		} else {
			$p = $vector->east($amount);
		}
		return $p;
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
}