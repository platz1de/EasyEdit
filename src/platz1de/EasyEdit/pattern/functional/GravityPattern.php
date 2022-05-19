<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\type\EmptyPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\HeightMapCache;

class GravityPattern extends Pattern
{
	use EmptyPatternData;

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current, Selection $total): int
	{
		HeightMapCache::load($iterator, $current);

		$originalY = $y;
		if (!HeightMapCache::isSolid($x, $y, $z)) {
			$y -= HeightMapCache::searchSolidDownwards($x, $y, $z) - 1;
		}
		return parent::getFor($x, $originalY, $z, $iterator, $current, $total);
	}
}