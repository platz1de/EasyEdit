<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\CylindricalConstructor;
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
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			CylindricalConstructor::aroundPoint($this->getPoint(), $this->getRadius(), $this->getHeight(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
		} else {
			if ($context->includesFilling()) {
				CylindricalConstructor::aroundPoint($this->getPoint(), $this->getRadius() - 1, $this->getHeight(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
			}

			if ($context->includesAllSides()) {
				CylindricalConstructor::hollowAround($this->getPoint(), $this->getRadius(), $context->getSideThickness(), $this->getHeight(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
			} elseif ($context->includesWalls()) {
				CylindricalConstructor::tubeAround($this->getPoint(), $this->getRadius(), $context->getSideThickness(), $this->getHeight(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
			}

			if ($context->includesCenter()) {
				CubicConstructor::single($this->getPoint(), $closure, Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max));
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