<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;

class CenterPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): bool
	{
		if ($current instanceof Cube) {
			$min = $total->getPos1();
			$max = $total->getPos2();

			$xPos = ($min->getX() + $max->getX()) / 2;
			$yPos = ($min->getY() + $max->getY()) / 2;
			$zPos = ($min->getZ() + $max->getZ()) / 2;

			return floor($xPos) <= $x && $x <= ceil($xPos) && floor($yPos) <= $y && $y <= ceil($yPos) && floor($zPos) <= $z && $z <= ceil($zPos);
		}
		if ($current instanceof Cylinder || $current instanceof Sphere) {
			return $x === $current->getPoint()->getFloorX() && $y === $current->getPoint()->getFloorY() && $z === $current->getPoint()->getFloorZ();
		}
		throw new ParseError("Center pattern does not support selection of type " . $current::class);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeCenter();
	}
}