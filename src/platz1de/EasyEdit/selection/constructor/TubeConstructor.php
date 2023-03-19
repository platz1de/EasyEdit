<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class TubeConstructor extends CylindricalConstructor
{
	/**
	 * @param Closure $closure
	 * @param Vector3 $position
	 * @param float   $radius
	 * @param int     $height
	 * @param float   $thickness
	 */
	public function __construct(Closure $closure, Vector3 $position, float $radius, int $height, private float $thickness)
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
		$posX = $this->position->getFloorX();
		$posY = $this->position->getFloorY();
		$posZ = $this->position->getFloorZ();
		$minX = max($min->getX() - $posX, -$radius);
		$maxX = min($max->getX() - $posX, $radius);
		$minZ = max($min->getZ() - $posZ, -$radius);
		$maxZ = min($max->getZ() - $posZ, $radius);
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

	public function offset(Vector3 $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->addVector($offset), $this->radius, $this->height, $this->thickness);
	}
}