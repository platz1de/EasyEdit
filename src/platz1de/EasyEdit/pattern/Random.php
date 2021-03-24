<?php

namespace platz1de\EasyEdit\pattern;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\Block;
use pocketmine\level\utils\SubChunkIteratorManager;

class Random extends Pattern
{
	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator, Selection $selection): bool
	{
		return true;
	}

	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		$selected = $this->pieces[array_rand($this->pieces)];
		if($selected->isValidAt($x, $y, $z, $iterator, $selection)){
			return $selected->getFor($x, $y, $z, $iterator, $selection);
		}
		return null;
	}
}