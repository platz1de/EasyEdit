<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\World;

class SphericalConstructor
{
	/**
	 * @param Vector3      $point
	 * @param float        $radius
	 * @param Closure      $closure
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 */
	public static function aroundPoint(Vector3 $point, float $radius, Closure $closure, Vector3 $min = null, Vector3 $max = null): void
	{
		if ($min === null) {
			$min = $point->subtract($radius, $radius, $radius);
		}
		if ($max === null) {
			$max = $point->add($radius, $radius, $radius);
		}
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$radius = ceil($radius);
		$minX = max($min->getX() - $point->getX(), -$radius);
		$maxX = min($max->getX() - $point->getX(), $radius);
		$minY = max($min->getY() - $point->getY(), -$radius, -$point->getY());
		$maxY = min($max->getY() - $point->getY(), $radius, World::Y_MAX - 1 - $point->getY());
		$minZ = max($min->getZ() - $point->getZ(), -$radius);
		$maxZ = min($max->getZ() - $point->getZ(), $radius);
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($point->getX() + $x, $point->getY() + $y, $point->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @param Vector3      $point
	 * @param float        $radius
	 * @param float        $thickness
	 * @param Closure      $closure
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 */
	public static function aroundPointHollow(Vector3 $point, float $radius, float $thickness, Closure $closure, Vector3 $min = null, Vector3 $max = null): void
	{
		if ($min === null) {
			$min = $point->subtract($radius, $radius, $radius);
		}
		if ($max === null) {
			$max = $point->add($radius, $radius, $radius);
		}
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$thicknessSquared = $thickness ** 2;
		$radius = ceil($radius);
		$minX = max($min->getX() - $point->getX(), -$radius);
		$maxX = min($max->getX() - $point->getX(), $radius);
		$minY = max($min->getY() - $point->getY(), -$radius, -$point->getY());
		$maxY = min($max->getY() - $point->getY(), $radius, World::Y_MAX - 1 - $point->getY());
		$minZ = max($min->getZ() - $point->getZ(), -$radius);
		$maxZ = min($max->getZ() - $point->getZ(), $radius);
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared && ($y === $minY || $y === $maxY || ($x ** 2) + ($y ** 2) + ($z ** 2) > $thicknessSquared)) {
						$closure($point->getX() + $x, $point->getY() + $y, $point->getZ() + $z);
					}
				}
			}
		}
	}
}