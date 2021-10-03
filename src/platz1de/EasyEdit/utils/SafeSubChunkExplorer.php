<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class SafeSubChunkExplorer extends SubChunkExplorer
{
	/** @var ReferencedChunkManager */
	protected $world;

	private int $writeCount = 0;
	private int $readCount = 0;

	/**
	 * @param ReferencedChunkManager $world
	 */
	public function __construct(ReferencedChunkManager $world)
	{
		parent::__construct($world);
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->world;
	}

	/**
	 * @return SubChunk
	 */
	public function getCurrent(): SubChunk
	{
		if ($this->currentSubChunk === null) {
			throw new BadMethodCallException("Tried to access unknown Chunk");
		}
		return $this->currentSubChunk;
	}

	/**
	 * @return Chunk
	 */
	public function getChunk(): Chunk
	{
		if ($this->currentChunk === null) {
			throw new BadMethodCallException("Tried to access unknown Chunk");
		}
		return $this->currentChunk;
	}

	/**
	 * @param Vector3 $vector
	 * @return int
	 */
	public function getBlock(Vector3 $vector): int
	{
		return $this->getBlockAt($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorX());
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getBlockAt(int $x, int $y, int $z): int
	{
		$this->readCount++;
		$y = (int) min(World::Y_MAX - 1, max(0, $y));
		$this->moveTo($x, $y, $z);
		return $this->getCurrent()->getFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f);
	}

	/**
	 * @param Vector3 $vector
	 * @param int     $block
	 */
	public function setBlock(Vector3 $vector, int $block): void
	{
		$this->setBlockAt($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorX(), $block);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $block
	 */
	public function setBlockAt(int $x, int $y, int $z, int $block): void
	{
		$this->writeCount++;
		$y = (int) min(World::Y_MAX - 1, max(0, $y));
		$this->moveTo($x, $y, $z);
		$this->getCurrent()->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $block);
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