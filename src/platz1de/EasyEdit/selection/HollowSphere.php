<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\piece\HollowSpherePiece;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class HollowSphere extends Sphere
{
	/**
	 * HollowSphere constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param int          $radius
	 * @param int          $thickness
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, int $thickness = 1, bool $piece = false)
	{
		parent::__construct($player, $level, $pos1, $radius, $piece);
		$this->setThickness($thickness);
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
		$thicknessSquared = ($radius - $this->getThickness()) ** 2;
		$minY = max(-$radius, -$this->pos1->getY());
		$maxY = min($radius, Level::Y_MASK - $this->pos1->getY());
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared && ($y === $minY || $y === $maxY || ($x ** 2) + ($y ** 2) + ($z ** 2) > $thicknessSquared)) {
						$closure($this->pos1->getX() + $x, $this->pos1->getY() + $y, $this->pos1->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function getThickness(): int
	{
		return $this->pos2->getFloorY();
	}

	/**
	 * @param int $thickness
	 */
	public function setThickness(int $thickness): void
	{
		$this->pos2->y = $thickness;
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return HollowSpherePiece[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$radius = $this->pos2->getFloorX();
		$pieces = [];
		for ($x = ($this->pos1->getX() - $radius) >> 4; $x <= ($this->pos1->getX() + $radius) >> 4; $x += 3) {
			for ($z = ($this->pos1->getZ() - $radius) >> 4; $z <= ($this->pos1->getZ() + $radius) >> 4; $z += 3) {
				$pieces[] = new HollowSpherePiece($this->getPlayer(), $this->getLevelName(), $this->pos1, new Vector3(max($x << 4, $this->pos1->getX() - $radius), max($this->pos1->getY() - $radius, 0), max($z << 4, $this->pos1->getZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getX() + $radius), min($this->pos1->getY() + $radius, Level::Y_MASK), min((($z + 2) << 4) + 15, $this->pos1->getZ() + $radius)), $radius, $this->getThickness());
			}
		}
		return $pieces;
	}
}