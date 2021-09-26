<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\World;

class CylindricalConstructor
{
	/**
	 * @param Vector3 $point
	 * @param float   $radius
	 * @param int     $height
	 * @param Closure $closure
	 */
	public static function aroundPoint(Vector3 $point, float $radius, int $height, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$radius = ceil($radius);
		$min = VectorUtils::enforceHeight($point);
		$maxY = min($point->getY() + $height - 1, World::Y_MAX - 1);
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $min->getY(); $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($point->getX() + $x, $y, $point->getZ() + $z);
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
	public static function tubeAround(Vector3 $point, float $radius, float $thickness, int $height, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$thicknessSquared = ($radius - $thickness) ** 2;
		$radius = ceil($radius);
		$min = VectorUtils::enforceHeight($point);
		$maxY = min($point->getY() + $height - 1, World::Y_MAX - 1);
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $min->getY(); $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared && ($x ** 2) + ($z ** 2) > $thicknessSquared) {
						$closure($point->getX() + $x, $y, $point->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @param Vector3 $point
	 * @param float   $radius
	 * @param float   $thickness
	 * @param int     $height
	 * @param Closure $closure
	 */
	public static function hollowAround(Vector3 $point, float $radius, float $thickness, int $height, Closure $closure): void
	{
		self::tubeAround($point->up(), $radius, $thickness, $height - 2, $closure);
		self::aroundPoint($point, $radius, 1, $closure);
		self::aroundPoint($point->up($height - 1), $radius, 1, $closure);
	}
}