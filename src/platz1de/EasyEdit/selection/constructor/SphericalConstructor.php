<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\World;

class SphericalConstructor
{
	/**
	 * @param Vector3 $point
	 * @param float   $radius
	 * @param Closure $closure
	 */
	public static function aroundPoint(Vector3 $point, float $radius, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$radius = ceil($radius);
		$minY = max(-ceil($radius), -$point->getY());
		$maxY = min($radius, World::Y_MAX - 1 - $point->getY());
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($point->getX() + $x, $point->getY() + $y, $point->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @param Vector3 $point
	 * @param float   $radius
	 * @param float   $thickness
	 * @param Closure $closure
	 */
	public static function aroundPointHollow(Vector3 $point, float $radius, float $thickness, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$thicknessSquared = $thickness ** 2;
		$radius = ceil($radius);
		$minY = max(-$radius, -$point->getY());
		$maxY = min($radius, World::Y_MAX - 1 - $point->getY());
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared && ($y === $minY || $y === $maxY || ($x ** 2) + ($y ** 2) + ($z ** 2) > $thicknessSquared)) {
						$closure($point->getX() + $x, $point->getY() + $y, $point->getZ() + $z);
					}
				}
			}
		}
	}
}