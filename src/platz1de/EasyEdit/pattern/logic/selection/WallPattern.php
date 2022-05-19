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
	private float $thickness;

	/**
	 * @param float     $thickness
	 * @param Pattern[] $pieces
	 */
	public function __construct(float $thickness, array $pieces)
	{
		parent::__construct($pieces);
		$this->thickness = $thickness;
	}

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

			return ($x - $min->getX() + 1) <= $this->thickness || ($max->getX() - $x + 1) <= $this->thickness || ($z - $min->getZ() + 1) <= $this->thickness || ($max->getZ() - $z - 1) <= $this->thickness;
		}
		if ($current instanceof Cylinder || $current instanceof Sphere) {
			return (($x - $current->getPoint()->getFloorX()) ** 2) + (($z - $current->getPoint()->getFloorZ()) ** 2) > (($current->getRadius() - $this->thickness) ** 2);
		}
		throw new ParseError("Walls pattern does not support selection of type " . $current::class);
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