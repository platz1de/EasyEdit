<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class EllipsoidConstructor extends ShapeConstructor
{
	/**
	 * @param Closure            $closure
	 * @param OffGridBlockVector $center
	 * @param float              $radiusX
	 * @param float              $radiusY
	 * @param float              $radiusZ
	 */
	public function __construct(Closure $closure, protected OffGridBlockVector $center, protected float $radiusX, protected float $radiusY, protected float $radiusZ)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		// Volume of an ellipsoid = (4/3) * π * a * b * c
		return (int) ((4 / 3) * M_PI * $this->radiusX * $this->radiusY * $this->radiusZ);
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$cenX = $this->center->x;
		$cenY = $this->center->y;
		$cenZ = $this->center->z;
		$radiusX = $this->radiusX;
		$radiusY = $this->radiusY;
		$radiusZ = $this->radiusZ;
		
		$minX = max($min->x, $cenX - ceil($radiusX));
		$maxX = min($max->x, $cenX + ceil($radiusX));
		$minY = max($min->y, $cenY - ceil($radiusY), World::Y_MIN);
		$maxY = min($max->y, $cenY + ceil($radiusY), World::Y_MAX - 1);
		$minZ = max($min->z, $cenZ - ceil($radiusZ));
		$maxZ = min($max->z, $cenZ + ceil($radiusZ));
		
		$radiusXSquared = $radiusX ** 2;
		$radiusYSquared = $radiusY ** 2;
		$radiusZSquared = $radiusZ ** 2;
		
		$closure = $this->closure;
		for ($x = $minX; $x <= $maxX; $x++) {
			$xDist = $x - $cenX;
			$xComponent = ($xDist ** 2) / $radiusXSquared;
			
			for ($z = $minZ; $z <= $maxZ; $z++) {
				$zDist = $z - $cenZ;
				$zComponent = ($zDist ** 2) / $radiusZSquared;
				
				for ($y = $minY; $y <= $maxY; $y++) {
					$yDist = $y - $cenY;
					$yComponent = ($yDist ** 2) / $radiusYSquared;
					
					// Check if the point is inside the ellipsoid using the equation (x²/a²) + (y²/b²) + (z²/c²) ≤ 1
					if ($xComponent + $yComponent + $zComponent <= 1) {
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
		
		// Check if the chunk intersects with the ellipsoid's bounding box
		$ellipsoidMin = $this->center->add(-$this->radiusX, -$this->radiusY, -$this->radiusZ);
		$ellipsoidMax = $this->center->add($this->radiusX, $this->radiusY, $this->radiusZ);
		
		return $min->x <= $ellipsoidMax->x && $max->x >= $ellipsoidMin->x &&
			   $min->y <= $ellipsoidMax->y && $max->y >= $ellipsoidMin->y &&
			   $min->z <= $ellipsoidMax->z && $max->z >= $ellipsoidMin->z;
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->center->offset($offset), $this->radiusX, $this->radiusY, $this->radiusZ);
	}
}