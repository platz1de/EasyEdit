<?php

namespace platz1de\EasyEdit\pattern\logic\math;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;

class EvenPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
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