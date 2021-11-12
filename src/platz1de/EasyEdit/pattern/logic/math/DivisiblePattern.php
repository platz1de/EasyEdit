<?php

namespace platz1de\EasyEdit\pattern\logic\math;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\WrongPatternUsageException;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;

class DivisiblePattern extends Pattern
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
		if ($this->args->checkXAxis() && abs($x) % $this->args->getInt("number") !== 0) {
			return false;
		}
		if ($this->args->checkXAxis() && abs($y) % $this->args->getInt("number") !== 0) {
			return false;
		}
		if ($this->args->checkXAxis() && abs($z) % $this->args->getInt("number") !== 0) {
			return false;
		}
		return true;
	}

	public function check(): void
	{
		if ($this->args->getInt("count") === -1) {
			throw new WrongPatternUsageException("Divisible needs a count argument");
		}
		if ($this->args->getInt("count") === 0) {
			throw new WrongPatternUsageException("Divisible can't be used with a count of zero");
		}
		if (!($this->args->checkXAxis() || $this->args->checkYAxis() || $this->args->checkZAxis())) {
			throw new WrongPatternUsageException("Even needs at least one axis, zero given");
		}
	}
}