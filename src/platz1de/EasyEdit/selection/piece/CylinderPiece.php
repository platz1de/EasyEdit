<?php

namespace platz1de\EasyEdit\selection\piece;

use Closure;
use platz1de\EasyEdit\selection\Cylinder;
use pocketmine\math\Vector3;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;

class CylinderPiece extends Cylinder
{
	/**
	 * @var Vector3
	 */
	protected $min;
	/**
	 * @var Vector3
	 */
	protected $max;

	/**
	 * CylinderPiece constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 * @param int          $radius
	 * @param int          $height
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, ?Vector3 $min = null, ?Vector3 $max = null, int $radius = 0, int $height = 0)
	{
		$this->min = $min;
		$this->max = $max;
		parent::__construct($player, $level, $pos1, $radius, $height, true);
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
	 * @param BinaryStream $stream
	 */
	public function putData(BinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putInt($this->min->getX());
		$stream->putInt($this->min->getY());
		$stream->putInt($this->min->getZ());
		$stream->putInt($this->max->getX());
		$stream->putInt($this->max->getY());
		$stream->putInt($this->max->getZ());
	}

	/**
	 * @param BinaryStream $stream
	 */
	public function parseData(BinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->min = new Vector3($stream->getInt(), $stream->getInt(), $stream->getInt());
		$this->max = new Vector3($stream->getInt(), $stream->getInt(), $stream->getInt());
	}
}