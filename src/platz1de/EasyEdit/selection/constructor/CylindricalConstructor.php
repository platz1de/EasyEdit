<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class CylindricalConstructor extends ShapeConstructor
{
	/**
	 * @param Closure            $closure
	 * @param OffGridBlockVector $position
	 * @param float              $radius
	 * @param int                $height
	 */
	public function __construct(Closure $closure, protected OffGridBlockVector $position, protected float $radius, protected int $height)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		return (int) (M_PI * ($this->radius ** 2) * $this->height);
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$radiusSquared = $this->radius ** 2;
		$radius = ceil($this->radius);
		$posX = $this->position->x;
		$posY = $this->position->y;
		$posZ = $this->position->z;
		$minX = max($min->x - $posX, -$radius);
		$maxX = min($max->x - $posX, $radius);
		$minZ = max($min->z - $posZ, -$radius);
		$maxZ = min($max->z - $posZ, $radius);
		$closure = $this->closure;
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $this->height - 1; $y >= 0; $y--) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($posX + $x, $posY + $y, $posZ + $z);
					}
				}
			}
		}
	}

	/**
	 * @param OffGridBlockVector $position
	 * @param float              $radius
	 * @param float              $thickness
	 * @param int                $height
	 * @param Closure            $closure
	 * @return Generator<ShapeConstructor>
	 */
	public static function hollowAround(OffGridBlockVector $position, float $radius, float $thickness, int $height, Closure $closure): Generator
	{
		yield new TubeConstructor($closure, $position->up(), $radius, $height - 2, $thickness);
		yield new self($closure, $position, $radius, (int) $thickness);
		yield new self($closure, $position->up($height - 1), $radius, (int) $thickness);
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->offset($offset), $this->radius, $this->height);
	}
}