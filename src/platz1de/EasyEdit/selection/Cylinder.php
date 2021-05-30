<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\piece\CylinderPiece;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Utils;
use RuntimeException;
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
		$pos2 = new Vector3($radius, $height); //This is not optimal, but currently needed...
		parent::__construct($player, $level, $pos1, $pos2, $piece);
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
	 * @noinspection StaticClosureCanBeUsedInspection
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(function (int $x, int $y, int $z): void { }, $closure);
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
		return $this->pos2->getX();
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
		return $this->pos2->getY();
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @return array
	 */
	public function split(): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$level = $this->getLevel();
		if ($level instanceof Level) {
			$level = $level->getFolderName();
		}
		$radius = $this->pos2->getX();
		$pieces = [];
		for ($x = ($this->pos1->getX() - $radius - 1) >> 4; $x <= ($this->pos1->getX() + $radius + 1) >> 4; $x += 3) {
			for ($z = ($this->pos1->getZ() - $radius - 1) >> 4; $z <= ($this->pos1->getZ() + $radius + 1) >> 4; $z += 3) {
				$pieces[] = new CylinderPiece($this->getPlayer(), $level, $this->pos1, new Vector3(max($x << 4, $this->pos1->getX() - $radius), max($this->pos1->getY(), 0), max($z << 4, $this->pos1->getZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getX() + $radius), min($this->pos1->getY() + $this->pos2->getY() - 1, Level::Y_MASK), min((($z + 2) << 4) + 15, $this->pos1->getZ() + $radius)), $radius, $this->pos2->getY());
			}
		}
		return $pieces;
	}
}