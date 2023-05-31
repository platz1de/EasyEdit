<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class TubeConstructor extends CylindricalConstructor
{
	/**
	 * @param Closure            $closure
	 * @param OffGridBlockVector $position
	 * @param float              $radius
	 * @param int                $height
	 * @param float              $thickness
	 */
	public function __construct(Closure $closure, OffGridBlockVector $position, float $radius, int $height, private float $thickness)
	{
		parent::__construct($closure, $position, $radius, $height);
	}

	public function getBlockCount(): int
	{
		return (int) (M_PI * (($this->radius ** 2) - ($this->radius - $this->thickness) ** 2) * $this->height);
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$radiusSquared = $this->radius ** 2;
		$thicknessSquared = ($this->radius - $this->thickness) ** 2;
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
					if (($x ** 2) + ($z ** 2) <= $radiusSquared && ($x ** 2) + ($z ** 2) > $thicknessSquared) {
						$closure($posX + $x, $posY + $y, $posZ + $z);
					}
				}
			}
		}
	}

	public function needsChunk(int $chunk): bool
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		if (!VectorUtils::checkCollisionCRH($this->position, $this->radius, $min, $max)) {
			return false; //outside
		}
		$posX = $this->position->x;
		$posZ = $this->position->z;
		$inner = ($this->radius - $this->thickness) ** 2;
		return !(($posX - $min->x) ** 2 + ($posZ - $min->z) ** 2 <= $inner && ($posX - $min->x) ** 2 + ($posZ - $max->z) ** 2 <= $inner && ($posX - $max->x) ** 2 + ($posZ - $min->z) ** 2 <= $inner && ($posX - $max->x) ** 2 + ($posZ - $max->z) ** 2 <= $inner);
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->offset($offset), $this->radius, $this->height, $this->thickness);
	}
}