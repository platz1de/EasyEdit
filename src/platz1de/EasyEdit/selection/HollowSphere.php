<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

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
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, int $thickness = 1, bool $piece = false)
	{
		$pos2 = new Vector3($radius, $thickness); //This is not optimal, but currently needed...
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
		$radiusSquared = $radius ** 2;
		$thicknessSquared = ($radius - $this->getThickness()) ** 2;
		$minY = max(-$radius, -$this->pos1->getY());
		$maxY = min($radius, Level::Y_MASK - $this->pos1->getY());
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					//TODO: round to height limit to not leave holes
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared && ($x ** 2) + ($y ** 2) + ($z ** 2) > $thicknessSquared) {
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
		return $this->pos2->getY();
	}

	/**
	 * @param int $thickness
	 */
	public function setThickness(int $thickness): void
	{
		$this->pos2->y = $thickness;
	}
}