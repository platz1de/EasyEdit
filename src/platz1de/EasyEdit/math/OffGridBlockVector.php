<?php

namespace platz1de\EasyEdit\math;

use pocketmine\world\World;

/**
 * These might be outside the world grid but still represent a block (e.g. for center of spheres, which can be cut off)
 */
class OffGridBlockVector extends BaseVector
{
	protected function validate(int &$x, int &$y, int &$z): void
	{
		//Nope
	}

	public function isInGrid(): bool
	{
		return $this->y >= World::Y_MIN && $this->y < World::Y_MAX;
	}

	public function forceIntoGrid(): BlockVector
	{
		return new BlockVector($this->x, $this->y, $this->z);
	}

	public function diff(OffGridBlockVector $other): BlockOffsetVector
	{
		return new BlockOffsetVector($this->x - $other->x, $this->y - $other->y, $this->z - $other->z);
	}

	public function offset(BlockOffsetVector $offset): OffGridBlockVector
	{
		return new OffGridBlockVector($this->x + $offset->x, $this->y + $offset->y, $this->z + $offset->z);
	}
}