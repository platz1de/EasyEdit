<?php

namespace platz1de\EasyEdit\selection\cubic;

use platz1de\EasyEdit\selection\Patterned;
use platz1de\EasyEdit\utils\LoaderManager;
use pocketmine\level\format\Chunk;
use pocketmine\world\Position;
use pocketmine\math\Vector3;

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
		$level = $this->getWorld();

		if ($this instanceof Patterned) {
			$start = $start->subtract(1, 1, 1);
			$end = $end->add(1, 1, 1);
		}

		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($level, $x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool
	{
		$start = $this->getCubicStart();
		$end = $this->getCubicEnd();

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}
}