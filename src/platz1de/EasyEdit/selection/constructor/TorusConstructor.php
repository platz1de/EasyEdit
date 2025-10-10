<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class TorusConstructor extends ShapeConstructor
{
	/**
	 * @param Closure            $closure
	 * @param OffGridBlockVector $position
	 * @param float              $majorRadius The distance from the center of the tube to the center of the torus
	 * @param float              $minorRadius The radius of the tube
	 */
	public function __construct(Closure $closure, protected OffGridBlockVector $position, protected float $majorRadius, protected float $minorRadius)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		// Volume of a torus = 2π² * R * r²
		return (int) (2 * M_PI * M_PI * $this->majorRadius * ($this->minorRadius ** 2));
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$posX = $this->position->x;
		$posY = $this->position->y;
		$posZ = $this->position->z;
		$majorRadius = $this->majorRadius;
		$minorRadius = $this->minorRadius;
		$totalRadius = $majorRadius + $minorRadius;
		
		$minX = max($min->x, $posX - ceil($totalRadius));
		$maxX = min($max->x, $posX + ceil($totalRadius));
		$minY = max($min->y, $posY - ceil($minorRadius));
		$maxY = min($max->y, $posY + ceil($minorRadius));
		$minZ = max($min->z, $posZ - ceil($totalRadius));
		$maxZ = min($max->z, $posZ + ceil($totalRadius));
		
		$closure = $this->closure;
		$minorRadiusSquared = $minorRadius ** 2;
		
		for ($x = $minX; $x <= $maxX; $x++) {
			$xDist = $x - $posX;
			
			for ($z = $minZ; $z <= $maxZ; $z++) {
				$zDist = $z - $posZ;
				
				// Calculate distance from the center of the torus to the current point in the XZ plane
				$distFromCenter = sqrt(($xDist ** 2) + ($zDist ** 2));
				
				// Skip if the point is too far from the ring
				if (abs($distFromCenter - $majorRadius) > $minorRadius) {
					continue;
				}
				
				for ($y = $minY; $y <= $maxY; $y++) {
					$yDist = $y - $posY;
					
					// Calculate the distance from the center of the tube
					// For each point (x,y,z), we calculate its distance to the circle of radius majorRadius in the XZ plane
					$distToTube = sqrt(($distFromCenter - $majorRadius) ** 2 + ($yDist ** 2));
					
					// If the distance is less than or equal to minorRadius, the point is inside the torus
					if ($distToTube <= $minorRadius) {
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
		
		// Check if the chunk intersects with the torus's bounding box
		$totalRadius = $this->majorRadius + $this->minorRadius;
		$torusMin = $this->position->add(-$totalRadius, -$this->minorRadius, -$totalRadius);
		$torusMax = $this->position->add($totalRadius, $this->minorRadius, $totalRadius);
		
		return $min->x <= $torusMax->x && $max->x >= $torusMin->x &&
			   $min->y <= $torusMax->y && $max->y >= $torusMin->y &&
			   $min->z <= $torusMax->z && $max->z >= $torusMin->z;
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->offset($offset), $this->majorRadius, $this->minorRadius);
	}
}