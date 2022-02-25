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

class WallPattern extends Pattern
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
		$thickness = $this->args->getFloat("thickness");
		if ($current instanceof Cube) {
			$min = $total->getPos1();
			$max = $total->getPos2();

			return ($x - $min->getX() + 1) <= $thickness || ($max->getX() - $x + 1) <= $thickness || ($z - $min->getZ() + 1) <= $thickness || ($max->getZ() - $z - 1) <= $thickness;
		}
		if ($current instanceof Cylinder || $current instanceof Sphere) {
			return (($x - $current->getPoint()->getFloorX()) ** 2) + (($z - $current->getPoint()->getFloorZ()) ** 2) > (($current->getRadius() - $thickness) ** 2);
		}
		throw new ParseError("Walls pattern does not support selection of type " . $current::class);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls($this->args->getFloat("thickness"));
	}

	public function check(): void
	{
		if ($this->args->getFloat("thickness") === -1.0) {
			$this->args->setFloat("thickness", 1.0);
		}
	}
}