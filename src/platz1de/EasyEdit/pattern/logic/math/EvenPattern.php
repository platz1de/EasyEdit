<?php

namespace platz1de\EasyEdit\pattern\logic\math;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\WrongPatternUsageException;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;

class EvenPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): bool
	{
		if ($this->args->checkXAxis() && abs($x) % 2 !== 0) {
			return false;
		}
		if ($this->args->checkYAxis() && abs($y) % 2 !== 0) {
			return false;
		}
		if ($this->args->checkZAxis() && abs($z) % 2 !== 0) {
			return false;
		}
		return true;
	}

	public function check(): void
	{
		if (!($this->args->checkXAxis() || $this->args->checkYAxis() || $this->args->checkZAxis())) {
			throw new WrongPatternUsageException("Even needs at least one axis, zero given");
		}
	}
}