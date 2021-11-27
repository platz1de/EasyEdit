<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

class CubicConstructor
{
	/**
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @param Closure $closure
	 */
	public static function betweenPoints(Vector3 $start, Vector3 $end, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$start = VectorUtils::enforceHeight($start);
		$end = VectorUtils::enforceHeight($end);
		for ($x = $start->getX(); $x <= $end->getX(); $x++) {
			for ($z = $start->getZ(); $z <= $end->getZ(); $z++) {
				for ($y = $start->getY(); $y <= $end->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @param int     $side
	 * @param float   $thickness
	 * @param Closure $closure
	 * @return void
	 */
	public static function onSide(Vector3 $start, Vector3 $end, int $side, float $thickness, Closure $closure): void
	{
		switch ($side) {
			case Facing::DOWN:
				self::betweenPoints($start, $end->withComponents(null, $start->getY() + $thickness - 1, null), $closure);
				break;
			case Facing::UP:
				self::betweenPoints($start->withComponents(null, $end->getY() - $thickness + 1, null), $end, $closure);
				break;
			case Facing::NORTH:
				self::betweenPoints($start, $end->withComponents(null, null, $start->getZ() + $thickness - 1), $closure);
				break;
			case Facing::SOUTH:
				self::betweenPoints($start->withComponents(null, null, $end->getZ() - $thickness + 1), $end, $closure);
				break;
			case Facing::WEST:
				self::betweenPoints($start, $end->withComponents($start->getX() + $thickness - 1, null, null), $closure);
				break;
			case Facing::EAST:
				self::betweenPoints($start->withComponents($end->getX() - $thickness + 1, null, null), $end, $closure);
		}
	}

	/**
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @param int[]   $sides
	 * @param float   $thickness
	 * @param Closure $closure
	 * @return void
	 */
	public static function onSides(Vector3 $start, Vector3 $end, array $sides, float $thickness, Closure $closure): void
	{
		//remove duplicate Blocks from sides
		$xStart = $start;
		$xEnd = $end;
		$zStart = $start;
		$zEnd = $end;

		if (in_array(Facing::DOWN, $sides, true)) {
			$xStart = $xStart->up();
			$zStart = $zStart->up();
		}
		if (in_array(Facing::UP, $sides, true)) {
			$xEnd = $xEnd->down();
			$zEnd = $zEnd->down();
		}

		if (in_array(Facing::NORTH, $sides, true)) {
			$xStart = $xStart->south();
		}
		if (in_array(Facing::SOUTH, $sides, true)) {
			$xEnd = $xEnd->north();
		}

		foreach ($sides as $side) {
			switch (Facing::axis($side)) {
				case Axis::Y:
					self::onSide($start, $end, $side, $thickness, $closure);
					break;
				case Axis::X:
					self::onSide($xStart, $xEnd, $side, $thickness, $closure);
					break;
				case Axis::Z:
					self::onSide($zStart, $zEnd, $side, $thickness, $closure);
			}
		}
	}

	/**
	 * @param Vector3      $block
	 * @param Closure      $closure
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 */
	public static function single(Vector3 $block, Closure $closure, Vector3 $min = null, Vector3 $max = null): void
	{
		if (($min === null || ($min->getX() <= $block->getX() && $min->getY() <= $block->getY() && $min->getZ() <= $block->getZ())) && ($max === null || ($max->getX() >= $block->getX() && $max->getY() >= $block->getY() && $max->getZ() >= $block->getZ()))) {
			$closure($block->getX(), $block->getY(), $block->getZ());
		}
	}
}