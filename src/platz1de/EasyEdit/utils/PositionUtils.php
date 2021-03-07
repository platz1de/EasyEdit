<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\level\Location;
use pocketmine\math\Vector3;

class PositionUtils
{
	/**
	 * @param Location $from
	 * @param Vector3  $vector
	 * @param int      $amount
	 * @return Vector3
	 */
	public static function moveVectorInSight(Location $from, Vector3 $vector, int $amount = 1): Vector3
	{
		if ($from->getPitch() >= 45) {
			$p = $vector->getSide(Vector3::SIDE_DOWN, $amount);
		} elseif ($from->getPitch() <= -45) {
			$p = $vector->getSide(Vector3::SIDE_UP, $amount);
		} elseif ($from->getYaw() >= 315 || $from->getYaw() < 45) {
			$p = $vector->getSide(Vector3::SIDE_SOUTH, $amount);
		} elseif ($from->getYaw() >= 45 && $from->getYaw() < 135) {
			$p = $vector->getSide(Vector3::SIDE_WEST, $amount);
		} elseif ($from->getYaw() >= 135 && $from->getYaw() < 225) {
			$p = $vector->getSide(Vector3::SIDE_NORTH, $amount);
		} else {
			$p = $vector->getSide(Vector3::SIDE_EAST, $amount);
		}
		return $p;
	}
}