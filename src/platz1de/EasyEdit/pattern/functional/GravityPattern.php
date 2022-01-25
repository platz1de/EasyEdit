<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\HeightMapCache;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;

class GravityPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): int
	{
		HeightMapCache::load($iterator, $current);

		$originalY = $y;
		if (!HeightMapCache::isSolid($x, $y, $z)) {
			$y -= HeightMapCache::searchSolidDownwards($x, $y, $z) - 1;
			HeightMapCache::moveUpwards($x, $y - 1, $z);
		}
		return parent::getFor($x, $originalY, $z, $iterator, $current, $total);
	}
}