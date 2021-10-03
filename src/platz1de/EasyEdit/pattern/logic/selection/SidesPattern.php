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
		$thickness = $this->args->getFloat("thickness");
		if ($selection instanceof Cube) {
			$min = TaskCache::getFullSelection()->getPos1();
			$max = TaskCache::getFullSelection()->getPos2();

			return ($x - $min->getX() + 1) <= $thickness || ($max->getX() - $x + 1) <= $thickness || ($y - $min->getY() + 1) <= $thickness || ($max->getY() - $y + 1) <= $thickness || ($z - $min->getZ() + 1) <= $thickness || ($max->getZ() - $z + 1) <= $thickness;
		}
		if ($selection instanceof Cylinder) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - $thickness) ** 2) || ($y - $selection->getPos1()->getY() + 1) <= $thickness || ($selection->getPos2()->getY() - $y + 1) <= $thickness;
		}
		if ($selection instanceof Sphere) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($y - $selection->getPoint()->getFloorY()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - $thickness) ** 2) || ($y - $selection->getPos1()->getY() + 1) <= $thickness || ($selection->getPos2()->getY() - $y + 1) <= $thickness;
		}
		throw new ParseError("Sides pattern does not support selection of type " . $selection::class);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls($this->args->getFloat("thickness"))->includeVerticals(0);
	}

	public function check(): void
	{
		if ($this->args->getFloat("thickness") === -1.0) {
			$this->args->setFloat("thickness", 1.0);
		}
	}
}