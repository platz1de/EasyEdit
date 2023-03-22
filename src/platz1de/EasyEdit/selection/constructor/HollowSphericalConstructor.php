<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class HollowSphericalConstructor extends SphericalConstructor
{
	/**
	 * @param Closure            $closure
	 * @param OffGridBlockVector $center
	 * @param float              $radius
	 * @param float              $thickness
	 */
	public function __construct(Closure $closure, OffGridBlockVector $center, float $radius, private float $thickness)
	{
		parent::__construct($closure, $center, $radius);
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
		$cenX = $this->center->x;
		$cenY = $this->center->y;
		$cenZ = $this->center->z;
		$minX = max($min->x - $cenX, -$radius);
		$maxX = min($max->x - $cenX, $radius);
		$minY = max($min->y - $cenY, -$radius, World::Y_MIN - $this->center->y);
		$maxY = min($max->y - $cenY, $radius, World::Y_MAX - 1 - $this->center->y);
		$minZ = max($min->z - $cenZ, -$radius);
		$maxZ = min($max->z - $cenZ, $radius);
		$closure = $this->closure;
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared && ($y === $minY || $y === $maxY || ($x ** 2) + ($y ** 2) + ($z ** 2) > $thicknessSquared)) {
						$closure($cenX + $x, $cenY + $y, $cenZ + $z);
					}
				}
			}
		}
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->closure, $this->center->offset($offset), $this->radius, $this->thickness);
	}
}