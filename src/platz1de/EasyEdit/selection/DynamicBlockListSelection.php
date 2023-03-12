<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
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
		return new self($pos2->subtractVector($pos1)->up(World::Y_MIN), $pos1->down(World::Y_MIN), $pos1->subtractVector($place)->down(World::Y_MIN));
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		return $this->getNonEmptyChunks($this->getPos1()->addVector($this->getPoint()), $this->getPos2()->addVector($this->getPoint()));
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		yield new CubicConstructor($closure, $this->getPos1()->addVector($this->getPoint()), $this->getPos2()->addVector($this->getPoint()));
	}

	/**
	 * @param int $chunk
	 * @return Generator<CompoundTag>
	 */
	public function getOffsetTiles(int $chunk): Generator
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		return $this->getTiles(Vector3::maxComponents($this->getPos1(), $min->subtractVector($this->getPoint())), Vector3::minComponents($this->getPos2(), $max->subtractVector($this->getPoint())));
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
		foreach ($this->getTiles($this->getPos1(), $this->getPos2()) as $tile) {
			$clone->addTile($tile);
		}
		return $clone;
	}
}