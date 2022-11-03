<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class HollowSphericalConstructor extends SphericalConstructor
{
	private float $thickness;

	/**
	 * @param Closure $closure
	 * @param Vector3 $center
	 * @param float   $radius
	 * @param float   $thickness
	 */
	public function __construct(Closure $closure, Vector3 $center, float $radius, float $thickness)
	{
		parent::__construct($closure, $center, $radius);
		$this->thickness = $thickness;
	}

	public function getBlockCount(): int
	{
		return (int) (4 / 3 * M_PI * ($this->radius ** 3 - ($this->radius - $this->thickness) ** 3));
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$radiusSquared = $this->radius ** 2;
		$thicknessSquared = ($this->radius - $this->thickness) ** 2;
		$radius = ceil($this->radius);
		$minX = max($min->getX() - $this->center->getX(), -$radius);
		$maxX = min($max->getX() - $this->center->getX(), $radius);
		$minY = max($min->getY() - $this->center->getY(), -$radius, -$this->center->getY());
		$maxY = min($max->getY() - $this->center->getY(), $radius, World::Y_MAX - 1 - $this->center->getY());
		$minZ = max($min->getZ() - $this->center->getZ(), -$radius);
		$maxZ = min($max->getZ() - $this->center->getZ(), $radius);
		$closure = $this->closure;
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared && ($y === $minY || $y === $maxY || ($x ** 2) + ($y ** 2) + ($z ** 2) > $thicknessSquared)) {
						$closure($this->center->getX() + $x, $this->center->getY() + $y, $this->center->getZ() + $z);
					}
				}
			}
		}
	}

	public function offset(Vector3 $offset): ShapeConstructor
	{
		return new self($this->closure, $this->center->addVector($offset), $this->radius, $this->thickness);
	}
}