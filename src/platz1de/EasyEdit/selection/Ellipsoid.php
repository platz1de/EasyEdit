<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\constructor\EllipsoidConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class Ellipsoid extends Selection
{
	use CubicChunkLoader;

	/**
	 * @param string             $world
	 * @param OffGridBlockVector $point
	 * @param float              $radiusX
	 * @param float              $radiusY
	 * @param float              $radiusZ
	 */
	public function __construct(string $world, private OffGridBlockVector $point, private float $radiusX, private float $radiusY, private float $radiusZ)
	{
		parent::__construct(
			$world, 
			$point->offset(new BlockOffsetVector((int) -ceil($radiusX), (int) -ceil($radiusY), (int) -ceil($radiusZ)))->forceIntoGrid(), 
			$point->offset(new BlockOffsetVector((int) ceil($radiusX), (int) ceil($radiusY), (int) ceil($radiusZ)))->forceIntoGrid()
		);
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
			yield new EllipsoidConstructor($closure, $this->getPoint(), $this->getRadiusX(), $this->getRadiusY(), $this->getRadiusZ());
		} else {
			if ($context->includesFilling()) {
				yield new EllipsoidConstructor($closure, $this->getPoint(), $this->getRadiusX() - 1, $this->getRadiusY() - 1, $this->getRadiusZ() - 1);
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
	 * @param float $radiusX
	 */
	public function setRadiusX(float $radiusX): void
	{
		$this->radiusX = $radiusX;
	}

	/**
	 * @return float
	 */
	public function getRadiusX(): float
	{
		return $this->radiusX;
	}

	/**
	 * @param float $radiusY
	 */
	public function setRadiusY(float $radiusY): void
	{
		$this->radiusY = $radiusY;
	}

	/**
	 * @return float
	 */
	public function getRadiusY(): float
	{
		return $this->radiusY;
	}

	/**
	 * @param float $radiusZ
	 */
	public function setRadiusZ(float $radiusZ): void
	{
		$this->radiusZ = $radiusZ;
	}

	/**
	 * @return float
	 */
	public function getRadiusZ(): float
	{
		return $this->radiusZ;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putBlockVector($this->point);
		$stream->putFloat($this->radiusX);
		$stream->putFloat($this->radiusY);
		$stream->putFloat($this->radiusZ);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getOffGridBlockVector();
		$this->radiusX = $stream->getFloat();
		$this->radiusY = $stream->getFloat();
		$this->radiusZ = $stream->getFloat();
	}
}