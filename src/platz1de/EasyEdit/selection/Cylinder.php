<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\CylindricalConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use platz1de\EasyEdit\selection\constructor\TubeConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;

class Cylinder extends Selection implements Patterned
{
	use CubicChunkLoader;

	private Vector3 $point;
	private float $radius;
	private int $height;

	/**
	 * @param string  $world
	 * @param Vector3 $point
	 * @param float   $radius
	 * @param int     $height
	 * @return Cylinder
	 */
	public static function aroundPoint(string $world, Vector3 $point, float $radius, int $height): Cylinder
	{
		$cylinder = new Cylinder($world, $point->subtract($radius, 0, $radius), $point->add($radius, $height - 1, $radius));
		$cylinder->setPoint($point);
		$cylinder->setRadius($radius);
		$cylinder->setHeight($height);
		return $cylinder;
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

			if ($context->includesCenter()) {
				yield new SingleBlockConstructor($closure, $this->getPoint());
			}
		}
	}

	/**
	 * @param Vector3 $point
	 */
	public function setPoint(Vector3 $point): void
	{
		$this->point = $point;
	}

	/**
	 * @return Vector3
	 */
	public function getPoint(): Vector3
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

		$stream->putVector($this->point);
		$stream->putFloat($this->radius);
		$stream->putInt($this->height);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getVector();
		$this->radius = $stream->getFloat();
		$this->height = $stream->getInt();
	}

}