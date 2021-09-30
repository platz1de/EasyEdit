<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

class CylindricalConstructor
{
	/**
	 * @param Vector3      $point
	 * @param float        $radius
	 * @param int          $height
	 * @param Closure      $closure
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 */
	public static function aroundPoint(Vector3 $point, float $radius, int $height, Closure $closure, Vector3 $min = null, Vector3 $max = null): void
	{
		if ($min === null) {
			$min = $point->subtract($radius, 0, $radius);
		}
		if ($max === null) {
			$max = $point->add($radius, $height - 1, $radius);
		}
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$radius = ceil($radius);
		$minX = max($min->getX() - $point->getX(), -$radius);
		$maxX = min($max->getX() - $point->getX(), $radius);
		$minY = VectorUtils::enforceHeight($min)->getY();
		$maxY = VectorUtils::enforceHeight($max)->getY();
		$minZ = max($min->getZ() - $point->getZ(), -$radius);
		$maxZ = min($max->getZ() - $point->getZ(), $radius);
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($point->getX() + $x, $y, $point->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @param Vector3      $point
	 * @param float        $radius
	 * @param float        $thickness
	 * @param int          $height
	 * @param Closure      $closure
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 */
	public static function tubeAround(Vector3 $point, float $radius, float $thickness, int $height, Closure $closure, Vector3 $min = null, Vector3 $max = null): void
	{
		if ($min === null) {
			$min = $point->subtract($radius, 0, $radius);
		}
		if ($max === null) {
			$max = $point->add($radius, $height - 1, $radius);
		}
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radiusSquared = $radius ** 2;
		$thicknessSquared = ($radius - $thickness) ** 2;
		$radius = ceil($radius);
		$minX = max($min->getX() - $point->getX(), -$radius);
		$maxX = min($max->getX() - $point->getX(), $radius);
		$minY = VectorUtils::enforceHeight($min)->getY();
		$maxY = VectorUtils::enforceHeight($max)->getY();
		$minZ = max($min->getZ() - $point->getZ(), -$radius);
		$maxZ = min($max->getZ() - $point->getZ(), $radius);
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
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
	public static function hollowAround(Vector3 $point, float $radius, float $thickness, int $height, Closure $closure, Vector3 $min = null, Vector3 $max = null): void
	{
		self::tubeAround($point->up(), $radius, $thickness, $height - 2, $closure, $min, $max);
		self::aroundPoint($point, $radius, 1, $closure, $min, $max);
		self::aroundPoint($point->up($height - 1), $radius, 1, $closure, $min, $max);
	}
}