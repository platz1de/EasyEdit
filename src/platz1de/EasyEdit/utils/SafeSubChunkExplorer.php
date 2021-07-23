<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;

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
}