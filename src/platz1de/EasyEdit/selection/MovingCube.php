<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\Position;
use pocketmine\world\World;

class MovingCube extends Selection
{
	private Vector3 $direction;

	/**
	 * MovingCube constructor.
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param Vector3|null $direction
	 */
	public function __construct(string $player, string $world = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, ?Vector3 $direction = null)
	{
		parent::__construct($player, $world, $pos1, $pos2);
		$this->direction = $direction ?? new Vector3(0, 0, 0);
	}

	/**
	 * @return Vector3
	 */
	public function getDirection(): Vector3
	{
		return $this->direction;
	}

	/**
	 * @param Position $place
	 * @return int[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		//TODO: Remove duplicates
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x++) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		for ($x = ($this->pos1->getX() + $this->direction->getX()) >> 4; $x <= ($this->pos2->getX() + $this->direction->getX()) >> 4; $x++) {
			for ($z = ($this->pos1->getZ() + $this->direction->getZ()) >> 4; $z <= ($this->pos2->getZ() + $this->direction->getZ()) >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool
	{
		$start = $this->getCubicStart();
		$end = $this->getCubicEnd();

		$start2 = $start->addVector($this->direction);
		$end2 = $end->addVector($this->direction);

		return ($start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4) || ($start2->getX() >> 4 <= $x && $x <= $end2->getX() >> 4 && $start2->getZ() >> 4 <= $z && $z <= $end2->getZ() >> 4);
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @param int     $context
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure, int $context = SelectionContext::FULL): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$min = VectorUtils::enforceHeight($this->pos1);
		$max = VectorUtils::enforceHeight($this->pos2);
		for ($this->direction->getX() > 0 ? $x = $max->getX() : $x = $min->getX(); $this->direction->getX() > 0 ? $x >= $min->getX() : $x <= $max->getX(); $this->direction->getX() > 0 ? $x-- : $x++) {
			for ($this->direction->getZ() > 0 ? $z = $max->getZ() : $z = $min->getZ(); $this->direction->getZ() > 0 ? $z >= $min->getZ() : $z <= $max->getZ(); $this->direction->getZ() > 0 ? $z-- : $z++) {
				for ($this->direction->getY() > 0 ? $y = $max->getY() : $y = $min->getY(); $this->direction->getY() > 0 ? $y >= $min->getY() : $y <= $max->getY(); $this->direction->getY() > 0 ? $y-- : $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return VectorUtils::getMin($this->getPos1(), $this->getPos1()->addVector($this->direction));
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		//TODO: don't add all blocks in between the positions
		return VectorUtils::getMax($this->getPos2(), $this->getPos2()->addVector($this->direction));
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->direction);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->direction = $stream->getVector();
	}

	/**
	 * @param Vector3 $offset
	 * @return Selection[]
	 */
	public function split(Vector3 $offset): array
	{
		//TODO
		return parent::split($offset);
	}
}