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

	public function useOnBlocks(Vector3 $place, Closure $closure, int $context = SelectionContext::FULL): void
	{
		if ($context === SelectionContext::NONE) {
			return;
		}

		if ($context === SelectionContext::FULL) {
			SphericalConstructor::aroundPoint($this->getPoint(), $this->getRadius(), $closure, $this->getPos1(), $this->getPos2());
		} else {
			if (($context & SelectionContext::FILLING) === SelectionContext::FILLING) {
				SphericalConstructor::aroundPoint($this->getPoint(), $this->getRadius() - 1, $closure, $this->getPos1(), $this->getPos2());
			}

			if (($context & SelectionContext::HOLLOW) === SelectionContext::HOLLOW) {
				SphericalConstructor::aroundPointHollow($this->getPoint(), $this->getRadius(), 1, $closure, $this->getPos1(), $this->getPos2());
			} elseif (($context & SelectionContext::WALLS) === SelectionContext::WALLS) {
				CylindricalConstructor::tubeAround($this->getPoint()->down((int) $this->getRadius()), $this->getRadius(), 1, (int) $this->getRadius() * 2 + 1, $closure, $this->getPos1(), $this->getPos2());
			} elseif (($context & SelectionContext::TOP_BOTTOM) === SelectionContext::TOP_BOTTOM) {
				CylindricalConstructor::aroundPoint($this->getPos1()->down((int) $this->getRadius()), $this->getRadius(), 1, $closure, $this->getPos1(), $this->getPos2());
				CylindricalConstructor::aroundPoint($this->getPos1()->up((int) $this->getRadius()), $this->getRadius(), 1, $closure, $this->getPos1(), $this->getPos2());
			}

			if (($context & SelectionContext::CENTER) === SelectionContext::CENTER) {
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
		$radius = $this->pos2->getFloorX();
		$pieces = [];
		for ($x = ($this->pos1->getX() - $radius) >> 4; $x <= ($this->pos1->getX() + $radius) >> 4; $x += 3) {
			for ($z = ($this->pos1->getZ() - $radius) >> 4; $z <= ($this->pos1->getZ() + $radius) >> 4; $z += 3) {
				$pieces[] = self::aroundPoint($this->getPlayer(), $this->getWorldName(), $this->getPoint(), $this->getRadius(), new Vector3(max($x << 4, $this->pos1->getFloorX() - $radius), max($this->pos1->getFloorY() - $radius, 0), max($z << 4, $this->pos1->getFloorZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getFloorX() + $radius), min($this->pos1->getFloorY() + $radius, World::Y_MAX - 1), min((($z + 2) << 4) + 15, $this->pos1->getFloorZ() + $radius)), true);
			}
		}
		return $pieces;
	}
}