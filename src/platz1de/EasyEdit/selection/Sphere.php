<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\CylindricalConstructor;
use platz1de\EasyEdit\selection\constructor\SphericalConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

class Sphere extends Selection implements Patterned
{
	use CubicChunkLoader;

	private Vector3 $point;
	private float $radius;

	/**
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3      $point
	 * @param float        $radius
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 * @param bool         $piece
	 * @return Sphere
	 */
	public static function aroundPoint(string $player, string $world, Vector3 $point, float $radius, ?Vector3 $min = null, ?Vector3 $max = null, bool $piece = false): Sphere
	{
		$sphere = new Sphere($player, $world, $min ?? $point->subtract($radius, $radius, $radius), $max ?? $point->add($radius, $radius, $radius), $piece);
		$sphere->setPoint($point);
		$sphere->setRadius($radius);
		return $sphere;
	}

	/**
	 * @param Vector3          $place
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context, Selection $full): void
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			SphericalConstructor::aroundPoint($this->getPoint(), $this->getRadius(), $closure, $this->getPos1(), $this->getPos2());
		} else {
			if ($context->includesFilling()) {
				SphericalConstructor::aroundPoint($this->getPoint(), $this->getRadius() - 1, $closure, $this->getPos1(), $this->getPos2());
			}

			if ($context->includesAllSides()) {
				SphericalConstructor::aroundPointHollow($this->getPoint(), $this->getRadius(), $context->getSideThickness(), $closure, $this->getPos1(), $this->getPos2());
			} elseif ($context->includesWalls()) {
				CylindricalConstructor::tubeAround($this->getPoint()->down((int) $this->getRadius()), $this->getRadius(), $context->getSideThickness(), (int) $this->getRadius() * 2 + 1, $closure, $this->getPos1(), $this->getPos2());
			}

			if ($context->includesCenter()) {
				CubicConstructor::single($this->getPos1(), $closure, $this->getPos1(), $this->getPos2());
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
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return Sphere[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		//TODO: offset
		$radius = $this->getRadius();
		$pieces = [];
		for ($x = ($this->point->getX() - $radius) >> 4; $x <= ($this->point->getX() + $radius) >> 4; $x += 3) {
			for ($z = ($this->point->getZ() - $radius) >> 4; $z <= ($this->point->getZ() + $radius) >> 4; $z += 3) {
				$pieces[] = self::aroundPoint($this->getPlayer(), $this->getWorldName(), $this->getPoint(), $this->getRadius(), new Vector3(max($x << 4, $this->pos1->getFloorX()), max($this->pos1->getFloorY(), 0), max($z << 4, $this->pos1->getFloorZ())), new Vector3(min((($x + 2) << 4) + 15, $this->pos2->getFloorX()), min($this->pos2->getFloorY(), World::Y_MAX - 1), min((($z + 2) << 4) + 15, $this->pos2->getFloorZ())), true);
			}
		}
		return $pieces;
	}
}