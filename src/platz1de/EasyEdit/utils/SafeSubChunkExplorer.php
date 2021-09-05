<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class SafeSubChunkExplorer extends SubChunkExplorer
{
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
		$y = (int) min(World::Y_MAX - 1, max(0, $y));
		$this->moveTo($x, $y, $z);
		$this->getCurrent()->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $block);
	}
}