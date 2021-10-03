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
		$thickness = $this->args->getFloat("thickness");
		if ($selection instanceof Cube) {
			$min = TaskCache::getFullSelection()->getPos1();
			$max = TaskCache::getFullSelection()->getPos2();

			return ($x - $min->getX() + 1) <= $thickness || ($max->getX() - $x + 1) <= $thickness || ($z - $min->getZ() + 1) <= $thickness || ($max->getZ() + $z - 1) <= $thickness;
		}
		if ($selection instanceof Cylinder || $selection instanceof Sphere) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - $thickness) ** 2);
		}
		throw new ParseError("Walls pattern does not support selection of type " . $selection::class);
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