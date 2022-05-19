<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\type\EmptyPatternData;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\world\ChunkController;

class CenterPattern extends Pattern
{
	use EmptyPatternData;

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