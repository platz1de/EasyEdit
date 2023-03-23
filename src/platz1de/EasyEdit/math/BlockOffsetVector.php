<?php

namespace platz1de\EasyEdit\math;

use pocketmine\world\World;

class BlockOffsetVector extends BaseVector
{
	protected function validate(&$x, &$y, &$z): void
	{
		$y = min(max($y, World::Y_MIN - World::Y_MAX + 1), World::Y_MAX - 1 - World::Y_MIN);
	}

	public function cubicVolume(): int
	{
		return (abs($this->x) + 1) * (abs($this->y) + 1) * (abs($this->z) + 1);
	}

	public function cubicSize(): BlockOffsetVector
	{
		return new BlockOffsetVector(abs($this->x) + 1, abs($this->y) + 1, abs($this->z) + 1);
	}

	public function negate(): BlockOffsetVector
	{
		return new BlockOffsetVector(-$this->x, -$this->y, -$this->z);
	}

	public function length(): float
	{
		return sqrt($this->x ** 2 + $this->y ** 2 + $this->z ** 2);
	}
}