<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\piece\HollowCylinderPiece;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class HollowCylinder extends Cylinder
{
	/**
	 * HollowCylinder constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param int          $radius
	 * @param int          $height
	 * @param int          $thickness
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, int $height = 0, int $thickness = 1, bool $piece = false)
	{
		parent::__construct($player, $level, $pos1, $radius, $height, $piece);
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
		$height = $this->pos2->getY();
		$radiusSquared = $radius ** 2;
		$thicknessSquared = ($radius - $this->getThickness()) ** 2;
		$min = VectorUtils::enforceHeight($this->pos1);
		$maxY = min($this->pos1->getY() + $height - 1, Level::Y_MASK);
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $min->getY(); $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared && ($y === $min->getY() || $y === $maxY || ($x ** 2) + ($z ** 2) > $thicknessSquared)) {
						$closure($this->pos1->getX() + $x, $y, $this->pos1->getZ() + $z);
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
		return $this->pos2->getFloorZ();
	}

	/**
	 * @param int $thickness
	 */
	public function setThickness(int $thickness): void
	{
		$this->pos2->z = $thickness;
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return HollowCylinderPiece[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		//TODO: offset
		$radius = $this->pos2->getFloorX();
		$pieces = [];
		for ($x = ($this->pos1->getX() - $radius - 1) >> 4; $x <= ($this->pos1->getX() + $radius + 1) >> 4; $x += 3) {
			for ($z = ($this->pos1->getZ() - $radius - 1) >> 4; $z <= ($this->pos1->getZ() + $radius + 1) >> 4; $z += 3) {
				$pieces[] = new HollowCylinderPiece($this->getPlayer(), $this->getLevelName(), $this->pos1, new Vector3(max($x << 4, $this->pos1->getFloorX() - $radius), max($this->pos1->getFloorY(), 0), max($z << 4, $this->pos1->getFloorZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getFloorX() + $radius), min($this->pos1->getFloorY() + $this->pos2->getFloorY(), Level::Y_MASK), min((($z + 2) << 4) + 15, $this->pos1->getFloorZ() + $radius)), $radius, $this->pos2->getFloorY(), $this->pos2->getFloorZ());
			}
		}
		return $pieces;
	}
}