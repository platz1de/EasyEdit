<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\piece\SpherePiece;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class Sphere extends Selection implements Patterned
{
	use CubicChunkLoader;

	/**
	 * Sphere constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param int          $radius
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, bool $piece = false)
	{
		$pos2 = new Vector3($radius); //This is not optimal, but currently needed...
		parent::__construct($player, $level, $pos1, $pos2, $piece);
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->getPos1()->subtract($this->getRadius(), $this->getRadius(), $this->getRadius());
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->getPos1()->add($this->getRadius(), $this->getRadius(), $this->getRadius());
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$radius = $this->pos2->getX();
		$radiusSquared = $radius ** 2;
		$minY = max(-$radius, -$this->pos1->getY());
		$maxY = min($radius, Level::Y_MASK - $this->pos1->getY());
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($this->pos1->getX() + $x, $this->pos1->getY() + $y, $this->pos1->getZ() + $z);
					}
				}
			}
		}
	}

	protected function update(): void
	{
		// don't mess everything up
	}

	/**
	 * @param Vector3 $pos
	 */
	public function setPos(Vector3 $pos): void
	{
		$this->pos1 = $pos;
	}

	/**
	 * @param int $radius
	 */
	public function setRadius(int $radius): void
	{
		$this->pos2->x = $radius;
	}

	/**
	 * @return int
	 */
	public function getRadius(): int
	{
		return $this->pos2->getFloorX();
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @return SpherePiece[]
	 */
	public function split(): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$radius = $this->pos2->getFloorX();
		$pieces = [];
		for ($x = ($this->pos1->getX() - $radius) >> 4; $x <= ($this->pos1->getX() + $radius) >> 4; $x += 3) {
			for ($z = ($this->pos1->getZ() - $radius) >> 4; $z <= ($this->pos1->getZ() + $radius) >> 4; $z += 3) {
				$pieces[] = new SpherePiece($this->getPlayer(), $this->getLevelName(), $this->pos1, new Vector3(max($x << 4, $this->pos1->getFloorX() - $radius), max($this->pos1->getFloorY() - $radius, 0), max($z << 4, $this->pos1->getFloorZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getFloorX() + $radius), min($this->pos1->getFloorY() + $radius, Level::Y_MASK), min((($z + 2) << 4) + 15, $this->pos1->getFloorZ() + $radius)), $radius);
			}
		}
		return $pieces;
	}
}