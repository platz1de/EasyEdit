<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class DynamicBlockListSelection extends ChunkManagedBlockList
{
	private Vector3 $point;
	private Vector3 $offset;

	/**
	 * DynamicBlockListSelection constructor.
	 * @param Vector3 $end
	 * @param Vector3 $worldOffset
	 * @param Vector3 $offset
	 */
	public function __construct(Vector3 $end, Vector3 $worldOffset, Vector3 $offset)
	{
		parent::__construct("", new Vector3(0, World::Y_MIN, 0), $end);
		$this->point = $offset;
		$this->offset = $worldOffset;
	}

	/**
	 * @param Vector3 $place
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @return DynamicBlockListSelection
	 */
	public static function fromWorldPositions(Vector3 $place, Vector3 $pos1, Vector3 $pos2): DynamicBlockListSelection
	{
		return new self($pos2->subtractVector($pos1)->up(World::Y_MIN), $pos1, $pos1->subtractVector($place));
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$start = $this->getCubicStart()->addVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($this->getPoint());

		$chunks = [];
		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function shouldBeCached(int $x, int $z): bool
	{
		$start = $this->getCubicStart()->addVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($this->getPoint());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && ($z === $end->getZ() >> 4 || $z === ($end->getZ() >> 4) + 1);
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
		CubicConstructor::betweenPoints(Vector3::maxComponents($this->getPos1()->addVector($this->getPoint()), $min), Vector3::minComponents($this->getPos2()->addVector($this->getPoint()), $max), $closure);
	}

	/**
	 * @return Vector3
	 */
	public function getPoint(): Vector3
	{
		return $this->point;
	}

	/**
	 * @param Vector3 $point
	 */
	public function setPoint(Vector3 $point): void
	{
		$this->point = $point;
	}

	/**
	 * @return Vector3
	 */
	public function getWorldOffset(): Vector3
	{
		return $this->offset;
	}

	/**
	 * @param Vector3 $offset
	 */
	public function setWorldOffset(Vector3 $offset): void
	{
		$this->offset = $offset;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->point);
		$stream->putVector($this->offset);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getVector();
		$this->offset = $stream->getVector();
	}

	public function createSafeClone(): DynamicBlockListSelection
	{
		$clone = new self($this->getPos2(), $this->getWorldOffset(), $this->getPoint());
		foreach ($this->getManager()->getChunks() as $hash => $chunk) {
			$clone->getManager()->setChunk($hash, $chunk);
		}
		foreach ($this->getTiles() as $tile) {
			$clone->addTile($tile);
		}
		return $clone;
	}
}