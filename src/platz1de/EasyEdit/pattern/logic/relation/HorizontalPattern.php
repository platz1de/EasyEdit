<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\type\BlockPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;

class HorizontalPattern extends Pattern
{
	use BlockPatternData;

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $current, Selection $total): bool
	{
		foreach ((new Vector3($x, $y, $z))->sidesAroundAxis(Axis::Y) as $side) {
			if ($this->block->equals($iterator->getBlock($side->getFloorX(), $side->getFloorY(), $side->getFloorZ()))) {
				return true;
			}
		}

		return false;
	}
}