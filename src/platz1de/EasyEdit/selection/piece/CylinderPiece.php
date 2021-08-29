<?php

namespace platz1de\EasyEdit\selection\piece;

use Closure;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

class CylinderPiece extends Cylinder
{
	protected Vector3 $min;
	protected Vector3 $max;

	/**
	 * CylinderPiece constructor.
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 * @param int          $radius
	 * @param int          $height
	 */
	public function __construct(string $player, string $world = "", ?Vector3 $pos1 = null, ?Vector3 $min = null, ?Vector3 $max = null, int $radius = 0, int $height = 0)
	{
		if ($min !== null) {
			$this->min = $min;
		}
		if ($max !== null) {
			$this->max = $max;
		}

		parent::__construct($player, $world, $pos1, $radius, $height, true);
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->min;
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->max;
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
		$minX = max($this->min->getX() - $this->pos1->getX(), -$radius);
		$maxX = min($this->max->getX() - $this->pos1->getX(), $radius);
		$minY = $this->min->getY();
		$maxY = $this->max->getY();
		$minZ = max($this->min->getZ() - $this->pos1->getZ(), -$radius);
		$maxZ = min($this->max->getZ() - $this->pos1->getZ(), $radius);
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($this->pos1->getX() + $x, $y, $this->pos1->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->min);
		$stream->putVector($this->max);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->min = $stream->getVector();
		$this->max = $stream->getVector();
	}
}