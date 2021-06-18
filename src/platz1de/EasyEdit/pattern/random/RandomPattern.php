<?php

namespace platz1de\EasyEdit\pattern\random;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use pocketmine\block\Block;

class RandomPattern extends Pattern
{
	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		$selected = $this->pieces[array_rand($this->pieces)];
		if ($selected->isValidAt($x, $y, $z, $iterator, $selection)) {
			return $selected->getFor($x, $y, $z, $iterator, $selection);
		}
		return null;
	}
}