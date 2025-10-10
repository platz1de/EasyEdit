<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class ConeConstructor extends ShapeConstructor
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
		// Volume of a cone = (1/3) * π * r² * h
		return (int) ((1 / 3) * M_PI * ($this->radius ** 2) * $this->height);
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$posX = $this->position->x;
		$posY = $this->position->y;
		$posZ = $this->position->z;
		$radius = $this->radius;
		$height = $this->height;
		
		$minX = max($min->x, $posX - ceil($radius));
		$maxX = min($max->x, $posX + ceil($radius));
		$minZ = max($min->z, $posZ - ceil($radius));
		$maxZ = min($max->z, $posZ + ceil($radius));
		$minY = max($min->y, $posY);
		$maxY = min($max->y, $posY + $height - 1);
		
		$closure = $this->closure;
		for ($y = $minY; $y <= $maxY; $y++) {
			$yLevel = $y - $posY;
			// Calculate the radius at this height level (linear interpolation)
			$levelRadius = $radius * (1 - ($yLevel / $height));
			$levelRadiusSquared = $levelRadius ** 2;
			
			for ($x = $minX; $x <= $maxX; $x++) {
				$xDist = $x - $posX;
				
				for ($z = $minZ; $z <= $maxZ; $z++) {
					$zDist = $z - $posZ;
					
					// Check if the point is within the cone at this height
					if (($xDist ** 2) + ($zDist ** 2) <= $levelRadiusSquared) {
						$closure($x, $y, $z);
					}
				}
			}
		}
	}

	public function needsChunk(int $chunk): bool
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		
		// Check if the chunk intersects with the cone's bounding box
		$coneMin = $this->position->add(-$this->radius, 0, -$this->radius);
		$coneMax = $this->position->add($this->radius, $this->height - 1, $this->radius);
		
		return $min->x <= $coneMax->x && $max->x >= $coneMin->x &&
			   $min->y <= $coneMax->y && $max->y >= $coneMin->y &&
			   $min->z <= $coneMax->z && $max->z >= $coneMin->z;
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->offset($offset), $this->radius, $this->height);
	}
}