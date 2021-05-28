<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\TaskCache;
use pocketmine\level\utils\SubChunkIteratorManager;

class SidesPattern extends Pattern
{
	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator, Selection $selection): bool
	{
		$min = TaskCache::getFullSelection()->getCubicStart();
		$max = TaskCache::getFullSelection()->getCubicEnd();
		//TODO: Non-Cubic Selections need unique checks
		return $x === $min->getX() || $x === $max->getX() || $y === $min->getY() || $y === $max->getY() || $z === $min->getZ() || $z === $max->getZ();
	}
}