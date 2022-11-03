<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use Generator;
use InvalidArgumentException;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class CubicConstructor extends ShapeConstructor
{
	private Vector3 $min;
	private Vector3 $max;

	/**
	 * @param Closure $closure
	 * @param Vector3 $min
	 * @param Vector3 $max
	 */
	public function __construct(Closure $closure, Vector3 $min, Vector3 $max)
	{
		parent::__construct($closure);
		$this->min = VectorUtils::enforceHeight($min);
		$this->max = VectorUtils::enforceHeight($max);
	}

	public function getBlockCount(): int
	{
		return (int) VectorUtils::product($this->max->subtractVector($this->min)->add(1, 1, 1));
	}

	public function moveTo(int $chunk): void
	{
		$min = Vector3::minComponents($this->min, VectorUtils::getChunkPosition($chunk));
		$max = Vector3::maxComponents($this->max, VectorUtils::getChunkPosition($chunk)->add(15, World::Y_MAX - World::Y_MIN - 1, 15));
		$closure = $this->closure;
		for ($x = $min->getX(); $x <= $max->getX(); $x++) {
			for ($z = $min->getZ(); $z <= $max->getZ(); $z++) {
				for ($y = $min->getY(); $y <= $max->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @param Vector3 $min
	 * @param Vector3 $max
	 * @param int     $side
	 * @param float   $thickness
	 * @param Closure $closure
	 * @return CubicConstructor
	 */
	public static function forSide(Vector3 $min, Vector3 $max, int $side, float $thickness, Closure $closure): CubicConstructor
	{
		return match ($side) {
			Facing::DOWN => new self($closure, $min, $max->withComponents(null, $min->getY() + $thickness - 1, null)),
			Facing::UP => new self($closure, $min->withComponents(null, $max->getY() - $thickness + 1, null), $max),
			Facing::NORTH => new self($closure, $min, $max->withComponents(null, null, $min->getZ() + $thickness - 1)),
			Facing::SOUTH => new self($closure, $min->withComponents(null, null, $max->getZ() - $thickness + 1), $max),
			Facing::WEST => new self($closure, $min, $max->withComponents($min->getX() + $thickness - 1, null, null)),
			Facing::EAST => new self($closure, $min->withComponents($max->getX() - $thickness + 1, null, null), $max),
			default => throw new InvalidArgumentException("Invalid side $side")
		};
	}

	/**
	 * @param Vector3 $min
	 * @param Vector3 $max
	 * @param int[]   $sides
	 * @param float   $thickness
	 * @param Closure $closure
	 * @return Generator<CubicConstructor>
	 */
	public static function forSides(Vector3 $min, Vector3 $max, array $sides, float $thickness, Closure $closure): Generator
	{
		//remove duplicate Blocks from sides (Priority: y, z, x)
		$xMin = $min;
		$xMax = $max;
		$zMin = $min;
		$zMax = $max;

		if (in_array(Facing::DOWN, $sides, true)) {
			$xMin = $xMin->up();
			$zMin = $zMin->up();
		}
		if (in_array(Facing::UP, $sides, true)) {
			$xMax = $xMax->down();
			$zMax = $zMax->down();
		}

		if (in_array(Facing::NORTH, $sides, true)) {
			$xMin = $xMin->south();
		}
		if (in_array(Facing::SOUTH, $sides, true)) {
			$xMax = $xMax->north();
		}

		foreach ($sides as $side) {
			switch (Facing::axis($side)) {
				case Axis::Y:
					yield self::forSide($min, $max, $side, $thickness, $closure);
					break;
				case Axis::X:
					yield self::forSide($xMin, $xMax, $side, $thickness, $closure);
					break;
				case Axis::Z:
					yield self::forSide($zMin, $zMax, $side, $thickness, $closure);
			}
		}
	}

	public function offset(Vector3 $offset): ShapeConstructor
	{
		return new self($this->closure, $this->min->addVector($offset), $this->max->addVector($offset));
	}
}