<?php

namespace platz1de\EasyEdit\selection\cubic;

use pocketmine\world\World;

trait CubicChunkLoader
{
	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$chunks = [];
		$start = $this->getPos1();
		$end = $this->getPos2();

		for ($x = $start->x >> 4; $x <= $end->x >> 4; $x++) {
			for ($z = $start->z >> 4; $z <= $end->z >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	///**
	// * @param int $x
	// * @param int $z
	// * @return bool
	// */
	//public function shouldBeCached(int $x, int $z): bool
	//{
	//	if ($this instanceof Patterned) {
	//		$start = $this->getCubicStart();
	//		$end = $this->getCubicEnd();
	//
	//		//we execute in z-direction first, caching x-direction is not efficient
	//		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && ($z === $end->getZ() >> 4 || $z === ($end->getZ() >> 4) + 1);
	//	}
	//
	//	return false; //No overlapping chunks needed
	//}
}