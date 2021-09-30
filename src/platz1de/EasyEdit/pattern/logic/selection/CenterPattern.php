<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TaskCache;

class CenterPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		$min = TaskCache::getFullSelection()->getCubicStart();
		$max = TaskCache::getFullSelection()->getCubicEnd();

		$xPos = ($min->getX() + $max->getX()) / 2;
		$yPos = ($min->getY() + $max->getY()) / 2;
		$zPos = ($min->getZ() + $max->getZ()) / 2;

		return floor($xPos) <= $x && $x <= ceil($xPos) && floor($yPos) <= $y && $y <= ceil($yPos) && floor($zPos) <= $z && $z <= ceil($zPos);
	}

	public function getSelectionContext(): int
	{
		return SelectionContext::CENTER;
	}
}