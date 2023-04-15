<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use UnexpectedValueException;

class DynamicBlockListSelection extends ChunkManagedBlockList
{
	/**
	 * DynamicBlockListSelection constructor.
	 * @param BlockOffsetVector $size   Actual size is one bigger in every direction (e.g. 1x1x0 contains 4 blocks)
	 * @param BlockOffsetVector $offset Offset from the world origin (to bottom of the selection)
	 * @param BlockOffsetVector $point  Pasting offset (to bottom of the selection)
	 */
	private function __construct(BlockOffsetVector $size, private BlockOffsetVector $offset, private BlockOffsetVector $point)
	{
		parent::__construct("", new BlockVector(0, World::Y_MIN, 0), (new BlockVector(0, World::Y_MAX, 0))->offset($size));
	}

	public static function empty(): self
	{
		return new self(new BlockOffsetVector(0, 0, 0), new BlockOffsetVector(0, -World::Y_MIN, 0), new BlockOffsetVector(0, World::Y_MIN, 0));
	}

	/**
	 * @param OffGridBlockVector $place
	 * @param BlockVector        $pos1
	 * @param BlockVector        $pos2
	 * @return DynamicBlockListSelection
	 */
	public static function fromWorldPositions(OffGridBlockVector $place, BlockVector $pos1, BlockVector $pos2): DynamicBlockListSelection
	{
		return new self($pos2->diff($pos1), $pos1->diff(new BlockVector(0, World::Y_MIN, 0)), $pos1->diff($place)->down(World::Y_MIN));
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$chunks = [];
		$offsets = [[-$this->point->x, -$this->point->z], [15 - $this->point->x, -$this->point->z], [-$this->point->x, 15 - $this->point->z], [15 - $this->point->x, 15 - $this->point->z]];
		$start = $this->getPos1()->offset($this->getPoint());
		$end = $this->getPos2()->offset($this->getPoint());
		for ($x = $start->x >> 4; $x <= $end->x >> 4; $x++) {
			for ($z = $start->z >> 4; $z <= $end->z >> 4; $z++) {
				for ($i = 0; $i < 4; $i++) {
					try {
						if (!$this->manager->getChunk(World::chunkHash((($x << 4) + $offsets[$i][0]) >> 4, (($z << 4) + $offsets[$i][1]) >> 4))->isEmpty()) {
							$chunks[] = World::chunkHash($x, $z);
							continue 2;
						}
					} catch (UnexpectedValueException) {
						continue;
					}
				}
			}
		}
		return $chunks;
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		yield new CubicConstructor($closure, $this->getPos1()->offset($this->getPoint()), $this->getPos2()->offset($this->getPoint()));
	}

	/**
	 * @param int $chunk
	 * @return Generator<CompoundTag>
	 */
	public function getOffsetTiles(int $chunk): Generator
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		return $this->getTiles(BlockVector::maxComponents($this->getPos1(), $min->offset($this->getPoint()->negate())), BlockVector::minComponents($this->getPos2(), $max->offset($this->getPoint()->negate())));
	}

	/**
	 * @return BlockOffsetVector
	 */
	public function getPoint(): BlockOffsetVector
	{
		return $this->point;
	}

	/**
	 * @param BlockOffsetVector $point
	 */
	public function setPoint(BlockOffsetVector $point): void
	{
		$this->point = $point;
	}

	/**
	 * @return BlockOffsetVector
	 */
	public function getWorldOffset(): BlockOffsetVector
	{
		return $this->offset;
	}

	/**
	 * @param BlockOffsetVector $offset
	 */
	public function setWorldOffset(BlockOffsetVector $offset): void
	{
		$this->offset = $offset;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putBlockVector($this->point);
		$stream->putBlockVector($this->offset);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getOffsetVector();
		$this->offset = $stream->getOffsetVector();
	}

	public function createSafeClone(): DynamicBlockListSelection
	{
		$clone = new self($this->getPos2()->diff(new BlockVector(0, World::Y_MIN, 0)), $this->getWorldOffset(), $this->getPoint());
		foreach ($this->getManager()->getChunks() as $hash => $chunk) {
			$clone->getManager()->setChunk($hash, $chunk);
		}
		foreach ($this->getTiles($this->getPos1(), $this->getPos2()) as $tile) {
			$clone->addTile($tile);
		}
		return $clone;
	}
}