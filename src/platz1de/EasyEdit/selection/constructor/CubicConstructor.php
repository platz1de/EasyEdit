<?php


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
	 * @param Closure $closure
	 * @return void
	 */
	public static function onSide(Vector3 $start, Vector3 $end, int $side, Closure $closure): void
	{
		switch ($side) {
			case Facing::DOWN:
				self::betweenPoints($start, $end->withComponents(null, $start->getY(), null), $closure);
				break;
			case Facing::UP:
				self::betweenPoints($start->withComponents(null, $end->getY(), null), $end, $closure);
				break;
			case Facing::NORTH:
				self::betweenPoints($start, $end->withComponents(null, null, $start->getZ()), $closure);
				break;
			case Facing::SOUTH:
				self::betweenPoints($start->withComponents(null, null, $end->getZ()), $end, $closure);
				break;
			case Facing::WEST:
				self::betweenPoints($start, $end->withComponents($start->getX(), null, null), $closure);
				break;
			case Facing::EAST:
				self::betweenPoints($start->withComponents($end->getX(), null, null), $end, $closure);
		}
	}

	/**
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @param int[]   $sides
	 * @param Closure $closure
	 * @return void
	 */
	public static function onSides(Vector3 $start, Vector3 $end, array $sides, Closure $closure): void
	{
		//remove duplicate Blocks from sides
		$xStart = $start;
		$xEnd = $end;
		$zStart = $start;
		$zEnd = $end;

		if (in_array(Facing::DOWN, $sides)) {
			$xStart = $xStart->up();
			$zStart = $zStart->up();
		}
		if (in_array(Facing::UP, $sides)) {
			$xEnd = $xEnd->down();
			$zEnd = $zEnd->down();
		}

		if (in_array(Facing::NORTH, $sides)) {
			$xStart = $xStart->south();
		}
		if (in_array(Facing::SOUTH, $sides)) {
			$xEnd = $xEnd->north();
		}

		foreach ($sides as $side) {
			switch (Facing::axis($side)) {
				case Axis::Y:
					self::onSide($start, $end, $side, $closure);
					break;
				case Axis::X:
					self::onSide($xStart, $xEnd, $side, $closure);
					break;
				case Axis::Z:
					self::onSide($zStart, $zEnd, $side, $closure);
			}
		}
	}
}