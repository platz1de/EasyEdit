<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\piece\CylinderPiece;
use platz1de\EasyEdit\selection\piece\HollowCylinderPiece;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\Level;
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
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, int $height = 0, int $thickness = 1, bool $piece = false)
	{
		$pos2 = new Vector3($radius, $height, $thickness); //This is not optimal, but currently needed...
		Selection::__construct($player, $level, $pos1, $pos2, $piece);
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
		return $this->pos2->getZ();
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
				$pieces[] = new HollowCylinderPiece($this->getPlayer(), $level, $this->pos1, new Vector3(max($x << 4, $this->pos1->getX() - $radius), max($this->pos1->getY(), 0), max($z << 4, $this->pos1->getZ() - $radius)), new Vector3(min((($x + 2) << 4) + 15, $this->pos1->getX() + $radius), min($this->pos1->getY() + $this->pos2->getY(), Level::Y_MASK), min((($z + 2) << 4) + 15, $this->pos1->getZ() + $radius)), $radius, $this->pos2->getY(), $this->pos2->getZ());
			}
		}
		return $pieces;
	}
}