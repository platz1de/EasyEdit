<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use pocketmine\world\format\Chunk;
use pocketmine\level\format\SubChunkInterface;
use pocketmine\level\utils\SubChunkIteratorManager;

class SafeSubChunkIteratorManager extends SubChunkIteratorManager
{
	/**
	 * @return SubChunkInterface
	 */
	public function getCurrent(): SubChunkInterface
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
}