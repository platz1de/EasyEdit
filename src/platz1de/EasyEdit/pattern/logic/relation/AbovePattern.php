<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\type\BlockPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\ChunkController;

class AbovePattern extends Pattern
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
		$y--;
		if ($y >= 0) {
			return $this->block->equals($iterator->getBlock($x, $y, $z));
		}
		return false;
	}
}