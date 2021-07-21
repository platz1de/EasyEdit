<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\world\World;
use pocketmine\level\Location;
use pocketmine\math\Vector3;

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
			$p = $vector->getSide(Vector3::SIDE_DOWN, $amount);
		} elseif ($pitch <= -45) {
			$p = $vector->getSide(Vector3::SIDE_UP, $amount);
		} elseif ($yaw >= 315 || $yaw < 45) {
			$p = $vector->getSide(Vector3::SIDE_SOUTH, $amount);
		} elseif ($yaw >= 45 && $yaw < 135) {
			$p = $vector->getSide(Vector3::SIDE_WEST, $amount);
		} elseif ($yaw >= 135 && $yaw < 225) {
			$p = $vector->getSide(Vector3::SIDE_NORTH, $amount);
		} else {
			$p = $vector->getSide(Vector3::SIDE_EAST, $amount);
		}
		return $p;
	}

	/**
	 * @param Vector3 ...$vector
	 * @return Vector3
	 */
	public static function getMax(Vector3 ...$vector): Vector3
	{
		return new Vector3((float) max(array_map(static function (Vector3 $b) { return $b->getX(); }, $vector)), (float) max(array_map(static function (Vector3 $b) { return $b->getY(); }, $vector)), (float) max(array_map(static function (Vector3 $b) { return $b->getZ(); }, $vector)));
	}

	/**
	 * @param Vector3 ...$vector
	 * @return Vector3
	 */
	public static function getMin(Vector3 ...$vector): Vector3
	{
		return new Vector3((float) min(array_map(static function (Vector3 $b) { return $b->getX(); }, $vector)), (float) min(array_map(static function (Vector3 $b) { return $b->getY(); }, $vector)), (float) min(array_map(static function (Vector3 $b) { return $b->getZ(); }, $vector)));
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
	 * @return Vector3
	 */
	public static function enforceHeight(Vector3 $vector): Vector3
	{
		return new Vector3($vector->getX(), min(World::Y_MASK, max(0, $vector->getY())), $vector->getZ());
	}
}