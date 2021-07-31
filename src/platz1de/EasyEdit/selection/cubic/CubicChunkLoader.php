<?php

namespace platz1de\EasyEdit\selection\cubic;

use platz1de\EasyEdit\selection\Patterned;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\world\World;

trait CubicChunkLoader
{
	/**
	 * @param Position $place
	 * @return int[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		$start = $this->getCubicStart();
		$end = $this->getCubicEnd();

		if ($this instanceof Patterned) {
			$start = $start->subtract(1, 1, 1);
			$end = $end->add(1, 1, 1);
		}

		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
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