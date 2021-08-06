<?php

namespace platz1de\EasyEdit\pattern\random;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;

class RandomPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): ?Block
	{
		$selected = $this->pieces[array_rand($this->pieces)];
		if ($selected->isValidAt($x, $y, $z, $iterator, $selection)) {
			return $selected->getFor($x, $y, $z, $iterator, $selection);
		}
		return null;
	}

	public function check(): void
	{
		if (count($this->pieces) < 2) {
			throw new ParseError("Random needs at least 2 child patterns");
		}
	}
}