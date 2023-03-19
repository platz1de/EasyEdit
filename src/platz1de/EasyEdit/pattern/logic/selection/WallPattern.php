<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;

class WallPattern extends Pattern
{
	/**
	 * @param float     $thickness
	 * @param Pattern[] $pieces
	 */
	public function __construct(private float $thickness, array $pieces)
	{
		parent::__construct($pieces);
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $selection): bool
	{
		if ($selection instanceof Cube) {
			$min = $selection->getPos1();
			$max = $selection->getPos2();

			return ($x - $min->getX() + 1) <= $this->thickness || ($max->getX() - $x + 1) <= $this->thickness || ($z - $min->getZ() + 1) <= $this->thickness || ($max->getZ() - $z + 1) <= $this->thickness;
		}
		if ($selection instanceof Cylinder || $selection instanceof Sphere) {
			return (($x - $selection->getPoint()->getFloorX()) ** 2) + (($z - $selection->getPoint()->getFloorZ()) ** 2) > (($selection->getRadius() - $this->thickness) ** 2);
		}
		throw new ParseError("Walls pattern does not support selection of type " . $selection::class);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls($this->thickness);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @return void
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putFloat($this->thickness);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @return void
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->thickness = $stream->getFloat();
	}
}