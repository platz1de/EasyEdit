<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\CylindricalConstructor;
use platz1de\EasyEdit\selection\constructor\SphericalConstructor;
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
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			SphericalConstructor::aroundPoint($this->getPoint(), $this->getRadius(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
		} else {
			if ($context->includesFilling()) {
				SphericalConstructor::aroundPoint($this->getPoint(), $this->getRadius() - 1, $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
			}

			if ($context->includesAllSides()) {
				SphericalConstructor::aroundPointHollow($this->getPoint(), $this->getRadius(), $context->getSideThickness(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
			} elseif ($context->includesWalls()) {
				CylindricalConstructor::tubeAround($this->getPoint()->down((int) $this->getRadius()), $this->getRadius(), $context->getSideThickness(), (int) $this->getRadius() * 2 + 1, $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
			}

			if ($context->includesCenter()) {
				CubicConstructor::single($this->getPos1(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
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

	/**
	 * @param Vector3 $vector
	 * @return Sphere
	 */
	public function offset(Vector3 $vector): self
	{
		return self::aroundPoint($this->getWorldName(), $this->getPoint()->addVector($vector), $this->getRadius());
	}
}