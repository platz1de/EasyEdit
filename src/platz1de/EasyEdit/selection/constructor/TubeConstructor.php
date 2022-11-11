<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class TubeConstructor extends CylindricalConstructor
{
	private float $thickness;

	/**
	 * @param Closure $closure
	 * @param Vector3 $position
	 * @param float   $radius
	 * @param int     $height
	 * @param float   $thickness
	 */
	public function __construct(Closure $closure, Vector3 $position, float $radius, int $height, float $thickness)
	{
		parent::__construct($closure, $position, $radius, $height);
		$this->thickness = $thickness;
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
		$minX = max($min->getX() - $this->position->getX(), -$radius);
		$maxX = min($max->getX() - $this->position->getX(), $radius);
		$minZ = max($min->getZ() - $this->position->getZ(), -$radius);
		$maxZ = min($max->getZ() - $this->position->getZ(), $radius);
		$closure = $this->closure;
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = 0; $y < $this->height; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared && ($x ** 2) + ($z ** 2) > $thicknessSquared) {
						$closure($this->position->getX() + $x, $this->position->getY() + $y, $this->position->getZ() + $z);
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