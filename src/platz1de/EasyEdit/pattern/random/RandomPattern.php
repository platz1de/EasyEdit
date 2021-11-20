<?php

namespace platz1de\EasyEdit\pattern\random;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;

class RandomPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return int
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): int
	{
		$selected = $this->pieces[array_rand($this->pieces)];
		if ($selected->isValidAt($x, $y, $z, $iterator, $current, $total)) {
			return $selected->getFor($x, $y, $z, $iterator, $current, $total);
		}
		return -1;
	}

	public function check(): void
	{
		if (count($this->pieces) < 2) {
			throw new WrongPatternUsageException("Random needs at least 2 child patterns");
		}
	}
}