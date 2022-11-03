<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\HollowSphericalConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use platz1de\EasyEdit\selection\constructor\SphericalConstructor;
use platz1de\EasyEdit\selection\constructor\TubeConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;

class Sphere extends Selection implements Patterned
{
	use CubicChunkLoader;

	private Vector3 $point;
	private float $radius;

	/**
	 * @param string  $world
	 * @param Vector3 $point
	 * @param float   $radius
	 * @return Sphere
	 */
	public static function aroundPoint(string $world, Vector3 $point, float $radius): Sphere
	{
		$sphere = new Sphere($world, $point->subtract($radius, $radius, $radius), $point->add($radius, $radius, $radius));
		$sphere->setPoint($point);
		$sphere->setRadius($radius);
		return $sphere;
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
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->point);
		$stream->putFloat($this->radius);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getVector();
		$this->radius = $stream->getFloat();
	}
}