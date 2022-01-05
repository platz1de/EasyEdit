<?php

namespace platz1de\EasyEdit\selection\cubic;

use platz1de\EasyEdit\selection\Patterned;
use pocketmine\world\World;

trait CubicChunkLoader
{
	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
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
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z): bool
	{
		$start = $this->getCubicStart();
		$end = $this->getCubicEnd();

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function shouldBeCached(int $x, int $z): bool
	{
		if ($this instanceof Patterned) {
			$start = $this->getCubicStart();
			$end = $this->getCubicEnd();

			//we execute in z-direction first, caching x-direction is not efficient
			return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && ($z === $end->getZ() >> 4 || $z === ($end->getZ() >> 4) + 1);
		}

		return false; //No overlapping chunks needed
	}
}