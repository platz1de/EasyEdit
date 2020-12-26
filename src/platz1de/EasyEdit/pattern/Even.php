<?php

namespace platz1de\EasyEdit\pattern;

use pocketmine\level\utils\SubChunkIteratorManager;

class Even extends Pattern
{
	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator): bool
	{
		if (abs($x) % 2 !== 0 && in_array("x", $this->args, true)) {
			return false;
		}
		if (abs($y) % 2 !== 0 && in_array("y", $this->args, true)) {
			return false;
		}
		if (abs($z) % 2 !== 0 && in_array("z", $this->args, true)) {
			return false;
		}
		return true;
	}
}