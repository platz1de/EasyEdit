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

class SidesPattern extends Pattern
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

			return $x === $min->getX() || $x === $max->getX() || $y === $min->getY() || $y === $max->getY() || $z === $min->getZ() || $z === $max->getZ();
		}
		if ($selection instanceof Cylinder) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - 1) ** 2) || $y === $selection->getPos1()->getFloorY() || $y === $selection->getPos2()->getFloorY();
		}
		if ($selection instanceof Sphere) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($y - $selection->getPoint()->getFloorY()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - 1) ** 2) || $y === $selection->getPos1()->getFloorY() || $y === $selection->getPos2()->getFloorY();
		}
		throw new ParseError("Sides pattern does not support selection of type " . $selection::class);
	}

	public function getSelectionContext(): int
	{
		return SelectionContext::HOLLOW;
	}
}