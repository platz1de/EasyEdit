<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\constructor\HollowSphericalConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use platz1de\EasyEdit\selection\constructor\SphericalConstructor;
use platz1de\EasyEdit\selection\constructor\TubeConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class Sphere extends Selection
{
	use CubicChunkLoader;

	/**
	 * @param string             $world
	 * @param OffGridBlockVector $point
	 * @param float              $radius
	 */
	public function __construct(string $world, private OffGridBlockVector $point, private float $radius)
	{
		parent::__construct($world, $point->offset(new BlockOffsetVector((int) -ceil($radius), (int) -ceil($radius), (int) -ceil($radius)))->forceIntoGrid(), $point->offset(new BlockOffsetVector((int) ceil($radius), (int) ceil($radius), (int) ceil($radius)))->forceIntoGrid());
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
			yield new SphericalConstructor($closure, $this->getPoint(), $this->getRadius());
		} else {
			if ($context->includesFilling()) {
				yield new SphericalConstructor($closure, $this->getPoint(), $this->getRadius() - 1);
			}

			if ($context->includesAllSides()) {
				yield new HollowSphericalConstructor($closure, $this->getPoint(), $this->getRadius(), $context->getSideThickness());
			} elseif ($context->includesWalls()) {
				yield new TubeConstructor($closure, $this->getPoint()->down((int) $this->getRadius()), $this->getRadius(), (int) $this->getRadius() * 2 + 1, $context->getSideThickness());
			}

			if ($context->includesCenter() && $this->point->isInGrid()) {
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
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putBlockVector($this->point);
		$stream->putFloat($this->radius);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getOffGridBlockVector();
		$this->radius = $stream->getFloat();
	}
}