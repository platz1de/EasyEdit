<?php

namespace platz1de\EasyEdit\selection\cubic;

use platz1de\EasyEdit\selection\Patterned;
use platz1de\EasyEdit\utils\LoaderManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\Position;

trait CubicChunkLoader
{
	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		$start = $this->getCubicStart();
		$end = $this->getCubicEnd();

		//Children sometimes implement this Interface but don't use Patterns at all
		//TODO: Fix weird class structure
		if ($this instanceof Patterned) {
			$start = $start->subtract(1, 1, 1);
			$end = $end->add(1, 1, 1);
		}

		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($this->getLevel(), $x, $z);
			}
		}
		return $chunks;
	}
}