<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class PyramidConstructor extends ShapeConstructor
{
	/**
	 * @param Closure            $closure
	 * @param OffGridBlockVector $position
	 * @param int                $size
	 * @param int                $height
	 */
	public function __construct(Closure $closure, protected OffGridBlockVector $position, protected int $size, protected int $height)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		// Volume of a pyramid = (1/3) * base area * height
		return (int) ((1 / 3) * ((2 * $this->size + 1) ** 2) * $this->height);
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$posX = $this->position->x;
		$posY = $this->position->y;
		$posZ = $this->position->z;
		$size = $this->size;
		$height = $this->height;
		
		$minX = max($min->x, $posX - $size);
		$maxX = min($max->x, $posX + $size);
		$minZ = max($min->z, $posZ - $size);
		$maxZ = min($max->z, $posZ + $size);
		$minY = max($min->y, $posY);
		$maxY = min($max->y, $posY + $height - 1);
		
		$closure = $this->closure;
		for ($y = $minY; $y <= $maxY; $y++) {
			$yLevel = $y - $posY;
			$levelSize = $size - (int) (($yLevel / ($height - 1)) * $size);
			
			for ($x = $minX; $x <= $maxX; $x++) {
				$xDist = abs($x - $posX);
				if ($xDist > $levelSize) continue;
				
				for ($z = $minZ; $z <= $maxZ; $z++) {
					$zDist = abs($z - $posZ);
					if ($zDist > $levelSize) continue;
					
					$closure($x, $y, $z);
				}
			}
		}
	}

	public function needsChunk(int $chunk): bool
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		
		// Check if the chunk intersects with the pyramid's bounding box
		$pyramidMin = $this->position->add(-$this->size, 0, -$this->size);
		$pyramidMax = $this->position->add($this->size, $this->height - 1, $this->size);
		
		return $min->x <= $pyramidMax->x && $max->x >= $pyramidMin->x &&
			   $min->y <= $pyramidMax->y && $max->y >= $pyramidMin->y &&
			   $min->z <= $pyramidMax->z && $max->z >= $pyramidMin->z;
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->offset($offset), $this->size, $this->height);
	}
}