<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class StackedCube extends Selection
{
	private Vector3 $direction;

	/**
	 * StackedCube constructor.
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
	 * @param Vector3 $place
	 * @return int[]
	 */
	public function getNeededChunks(Vector3 $place): array
	{
		$chunks = [];
		$start = VectorUtils::getMin($this->getCubicStart(), $this->getPos1());
		$end = VectorUtils::getMax($this->getCubicEnd(), $this->getPos2());

		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
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
		$start = VectorUtils::getMin($this->getCubicStart(), $this->getPos1());
		$end = VectorUtils::getMax($this->getCubicEnd(), $this->getPos2());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}

	/**
	 * @param Vector3          $place
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context, Selection $full): void
	{
		CubicConstructor::betweenPoints($this->getCubicStart(), $this->getCubicEnd(), $closure);
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
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return VectorUtils::getMin($this->getPos1()->addVector(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos1()->addVector(VectorUtils::multiply($this->getDirection(), $this->getSize())));
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return VectorUtils::getMax($this->getPos2()->addVector(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos2()->addVector(VectorUtils::multiply($this->getDirection(), $this->getSize())));
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