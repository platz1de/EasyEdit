<?php

namespace platz1de\EasyEdit\task;

use pocketmine\level\format\Chunk;
use pocketmine\level\SimpleChunkManager;

class ChunkManager extends SimpleChunkManager
{
	public function __construct()
	{
		parent::__construct(0);
	}

	/**
	 * @return Chunk[]
	 */
	public function getChunks(): array
	{
		return $this->chunks;
	}
}