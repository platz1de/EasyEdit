<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use platz1de\EasyEdit\utils\TaskCache;

class WallPattern extends Pattern
{
	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): bool
	{
		$min = TaskCache::getFullSelection()->getCubicStart();
		$max = TaskCache::getFullSelection()->getCubicEnd();
		//TODO: Non-Cubic Selections need unique checks
		return $x === $min->getX() || $x === $max->getX() || $z === $min->getZ() || $z === $max->getZ();
	}
}