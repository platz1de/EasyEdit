<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\piece\CylinderPiece;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class Cylinder extends Selection implements Patterned
{
	use CubicChunkLoader;

	/**
	 * Cylinder constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param int          $radius
	 * @param int          $height
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, int $height = 0, bool $piece = false)
	{
		parent::__construct($player, $level, $pos1, new Vector3(), $piece);
		$this->setRadius($radius);
		$this->setHeight($height);
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->getPos1()->subtract($this->getRadius(), 0, $this->getRadius());
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->getPos1()->add($this->getRadius(), $this->getHeight() - 1, $this->getRadius());
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
		$height = $this->pos2->getY();
		$radiusSquared = $radius ** 2;
		$min = VectorUtils::enforceHeight($this->pos1);
		$maxY = min($this->pos1->getY() + $height - 1, Level::Y_MASK);
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $min->getY(); $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($this->pos1->getX() + $x, $y, $this->pos1->getZ() + $z);
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
	 * @param int $radius
	 */
	public function setHeight(int $radius): void
	{
		$this->pos2->y = $radius;
	}

	/**
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->pos2->getFloorY();
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return CylinderPiece[]
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
				$pieces[] = new CylinderPiece($this->getPlayer(), $this->getLevelName(), $this->pos1, new Vector3(max($x << 4, $this->pos1->getFloorX() - $radius), max($this->pos1->getFloorY(), 0), max($z << 4, $this->pos1->getFloorZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getFloorX() + $radius), min($this->pos1->getFloorY() + $this->pos2->getFloorY() - 1, Level::Y_MASK), min((($z + 2) << 4) + 15, $this->pos1->getFloorZ() + $radius)), $radius, $this->pos2->getFloorY());
			}
		}
		return $pieces;
	}
}