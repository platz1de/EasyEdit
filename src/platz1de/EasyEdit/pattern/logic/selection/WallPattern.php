<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TaskCache;

class WallPattern extends Pattern
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
		if ($selection instanceof Cube) {
			$min = TaskCache::getFullSelection()->getPos1();
			$max = TaskCache::getFullSelection()->getPos2();

			return $x === $min->getX() || $x === $max->getX() || $z === $min->getZ() || $z === $max->getZ();
		}
		if ($selection instanceof Cylinder || $selection instanceof Sphere) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - 1) ** 2);
		}
		throw new ParseError("Walls pattern does not support selection of type " . $selection::class);
	}

	public function getSelectionContext(): int
	{
		return SelectionContext::WALLS;
	}
}