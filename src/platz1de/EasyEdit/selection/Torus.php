<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use platz1de\EasyEdit\selection\constructor\TorusConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class Torus extends Selection
{
	use CubicChunkLoader;

	/**
	 * @param string             $world
	 * @param OffGridBlockVector $point
	 * @param float              $majorRadius The distance from the center of the tube to the center of the torus
	 * @param float              $minorRadius The radius of the tube
	 */
	public function __construct(string $world, private OffGridBlockVector $point, private float $majorRadius, private float $minorRadius)
	{
		$totalRadius = $majorRadius + $minorRadius;
		parent::__construct(
			$world, 
			$point->offset(new BlockOffsetVector((int) -ceil($totalRadius), (int) -ceil($minorRadius), (int) -ceil($totalRadius)))->forceIntoGrid(), 
			$point->offset(new BlockOffsetVector((int) ceil($totalRadius), (int) ceil($minorRadius), (int) ceil($totalRadius)))->forceIntoGrid()
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
			yield new TorusConstructor($closure, $this->getPoint(), $this->getMajorRadius(), $this->getMinorRadius());
		} else {
			if ($context->includesFilling()) {
				yield new TorusConstructor($closure, $this->getPoint(), $this->getMajorRadius(), $this->getMinorRadius() - 1);
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
	 * @param float $majorRadius
	 */
	public function setMajorRadius(float $majorRadius): void
	{
		$this->majorRadius = $majorRadius;
	}

	/**
	 * @return float
	 */
	public function getMajorRadius(): float
	{
		return $this->majorRadius;
	}

	/**
	 * @param float $minorRadius
	 */
	public function setMinorRadius(float $minorRadius): void
	{
		$this->minorRadius = $minorRadius;
	}

	/**
	 * @return float
	 */
	public function getMinorRadius(): float
	{
		return $this->minorRadius;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putBlockVector($this->point);
		$stream->putFloat($this->majorRadius);
		$stream->putFloat($this->minorRadius);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getOffGridBlockVector();
		$this->majorRadius = $stream->getFloat();
		$this->minorRadius = $stream->getFloat();
	}
}