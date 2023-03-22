<?php

namespace platz1de\EasyEdit\math;

use pocketmine\world\World;

class BlockOffsetVector extends BaseVector
{
	protected function validate(&$x, &$y, &$z): void
	{
		$y = min(max($y, World::Y_MIN - World::Y_MAX + 1), World::Y_MAX - 1 - World::Y_MIN);
	}

	public function cubicArea(): int
	{
		return (abs($this->x) + 1) * (abs($this->y) + 1) * (abs($this->z) + 1);
	}
}