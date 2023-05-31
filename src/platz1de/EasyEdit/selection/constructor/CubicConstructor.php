<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use pocketmine\math\Facing;

class CubicConstructor extends ShapeConstructor
{
	/**
	 * @param Closure     $closure
	 * @param BlockVector $min
	 * @param BlockVector $max
	 */
	public function __construct(Closure $closure, private BlockVector $min, private BlockVector $max)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		return $this->max->diff($this->min)->cubicVolume();
	}

	public function moveTo(int $chunk): void
	{
		$min = $this->min->forceIntoChunkStart($chunk);
		$max = $this->max->forceIntoChunkEnd($chunk);
		$closure = $this->closure;
		$minX = $min->x;
		$minY = $min->y;
		$minZ = $min->z;
		$maxX = $max->x;
		$maxY = $max->y;
		$maxZ = $max->z;
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	public function needsChunk(int $chunk): bool
	{
		$min = $this->min->forceIntoChunkStart($chunk);
		$max = $this->max->forceIntoChunkEnd($chunk);
		return $min->x <= $max->x && $min->y <= $max->y && $min->z <= $max->z;
	}

	/**
	 * @param BlockVector $min
	 * @param BlockVector $max
	 * @param int         $side
	 * @param int         $thickness
	 * @param Closure     $closure
	 * @return CubicConstructor
	 */
	public static function forSide(BlockVector $min, BlockVector $max, int $side, int $thickness, Closure $closure): CubicConstructor
	{
		$axis = Facing::axis($side);
		if (Facing::isPositive($side)) {
			$min = $min->setComponent($axis, $max->getComponent($axis) + 1 - $thickness);
		} else {
			$max = $max->setComponent($axis, $min->getComponent($axis) - 1 + $thickness);
		}
		return new self($closure, $min, $max);
	}

	/**
	 * @param BlockVector $min
	 * @param BlockVector $max
	 * @param int[]       $sides
	 * @param int         $thickness
	 * @param Closure     $closure
	 * @return Generator<CubicConstructor>
	 */
	public static function forSides(BlockVector $min, BlockVector $max, array $sides, int $thickness, Closure $closure): Generator
	{
		$sides = array_unique($sides); //TODO: removing this can lead to interesting results as positions are shifted around (add this as a feature?)

		foreach ($sides as $side) {
			yield self::forSide($min, $max, $side, $thickness, $closure);

			//These blocks are now already done, so we can remove them from the selection
			if (Facing::isPositive($side)) {
				$max = $max->addComponent(Facing::axis($side), -$thickness);
			} else {
				$min = $min->addComponent(Facing::axis($side), $thickness);
			}
		}
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->min->offset($offset), $this->max->offset($offset));
	}
}