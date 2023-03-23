<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\constructor\CylindricalConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use platz1de\EasyEdit\selection\constructor\TubeConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class Cylinder extends Selection
{
	use CubicChunkLoader;

	/**
	 * @param string             $world
	 * @param OffGridBlockVector $point
	 * @param float              $radius
	 * @param int                $height
	 */
	public function __construct(string $world, private OffGridBlockVector $point, private float $radius, private int $height)
	{
		parent::__construct($world, $point->add(-$radius, 0, -$radius)->forceIntoGrid(), $point->add($radius, $height - 1, $radius)->forceIntoGrid());
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			yield new CylindricalConstructor($closure, $this->getPoint(), $this->getRadius(), $this->getHeight());
		} else {
			if ($context->includesFilling()) {
				yield new CylindricalConstructor($closure, $this->getPoint(), $this->getRadius() - 1, $this->getHeight());
			}

			if ($context->includesAllSides()) {
				yield from CylindricalConstructor::hollowAround($this->getPoint(), $this->getRadius(), $context->getSideThickness(), $this->getHeight(), $closure);
			} elseif ($context->includesWalls()) {
				yield new TubeConstructor($closure, $this->getPoint(), $this->getRadius(), $this->getHeight(), $context->getSideThickness());
			}

			if ($context->includesCenter() && $this->getPoint()->isInGrid()) {
				yield new SingleBlockConstructor($closure, $this->getPoint()->forceIntoGrid());
			}
		}
	}

	/**
	 * @param OffGridBlockVector $point
	 */
	public function setPoint(OffGridBlockVector $point): void
	{
		$this->point = $point;
	}

	/**
	 * @return OffGridBlockVector
	 */
	public function getPoint(): OffGridBlockVector
	{
		return $this->point;
	}

	/**
	 * @param float $radius
	 */
	public function setRadius(float $radius): void
	{
		$this->radius = $radius;
	}

	/**
	 * @return float
	 */
	public function getRadius(): float
	{
		return $this->radius;
	}

	/**
	 * @param int $height
	 */
	public function setHeight(int $height): void
	{
		$this->height = $height;
	}

	/**
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->height;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putBlockVector($this->point);
		$stream->putFloat($this->radius);
		$stream->putInt($this->height);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getOffGridBlockVector();
		$this->radius = $stream->getFloat();
		$this->height = $stream->getInt();
	}
}