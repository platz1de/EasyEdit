<?php

namespace platz1de\EasyEdit\world;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\SubChunk;
use pocketmine\world\World;
use UnexpectedValueException;

class ChunkController
{
	private ReferencedChunkManager $world;

	private int $writeCount = 0;
	private int $readCount = 0;

	public ChunkInformation $currentChunk;
	public SubChunk $currentSubChunk;

	protected int $currentX;
	protected int $currentY;
	protected int $currentZ;

	public function __construct(ReferencedChunkManager $world)
	{
		$this->world = $world;
		try {
			$this->init();
		} catch (UnexpectedValueException) { //needs to be init later
		}
	}

	public function init(): void
	{
		$chunks = $this->world->getChunks();
		if (count($chunks) === 0) {
			throw new UnexpectedValueException("No chunks loaded");
		}
		$this->currentChunk = current($chunks); //just a random chunk
		$this->currentChunk->use();
		$this->currentSubChunk = $this->currentChunk->getChunk()->getSubChunk(World::Y_MIN >> 4);
		World::getXZ(key($chunks), $x, $z);
		$this->currentX = $x;
		$this->currentY = World::Y_MIN >> 4;
		$this->currentZ = $z;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return bool Whether a move was needed
	 */
	public function moveTo(int $x, int $y, int $z): bool
	{
		if ($this->currentX !== $x >> 4 || $this->currentZ !== $z >> 4) {
			$this->currentX = $x >> 4;
			$this->currentZ = $z >> 4;

			$this->currentY = (World::Y_MIN >> 4) - 1; //invalidate

			$this->currentChunk = $this->world->getChunk($this->currentX, $this->currentZ);
			$this->currentChunk->use();
		}

		if ($this->currentY !== $y >> 4) {
			$this->currentY = $y >> 4;

			$this->currentSubChunk = $this->currentChunk->getChunk()->getSubChunk($y >> 4);
			return true;
		}
		return false;
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->world;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getBlock(int $x, int $y, int $z): int
	{
		$this->readCount++;
		$y = min(World::Y_MAX - 1, max(0, $y));
		$this->moveTo($x, $y, $z);
		return $this->currentSubChunk->getFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return CompoundTag|null
	 */
	public function getTile(int $x, int $y, int $z): ?CompoundTag
	{
		$this->moveTo($x, $y, $z);
		return $this->currentChunk->getTile($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $block
	 */
	public function setBlock(int $x, int $y, int $z, int $block): void
	{
		$this->writeCount++;
		$y = min(World::Y_MAX - 1, max(0, $y));
		$this->moveTo($x, $y, $z);
		$this->currentSubChunk->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $block);
	}

	/**
	 * @param int         $x
	 * @param int         $y
	 * @param int         $z
	 * @param CompoundTag $tile
	 */
	public function setTile(int $x, int $y, int $z, CompoundTag $tile): void
	{
		$this->moveTo($x, $y, $z);
		$this->currentChunk->setTile($x & 0x0f, $y, $z & 0x0f, $tile);
	}

	/**
	 * @return int
	 */
	public function getWrittenBlockCount(): int
	{
		return $this->writeCount;
	}

	/**
	 * @return int
	 */
	public function getReadBlockCount(): int
	{
		return $this->readCount;
	}
}