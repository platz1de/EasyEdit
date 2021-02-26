<?php

namespace platz1de\EasyEdit\pattern;

use pocketmine\block\Block;
use pocketmine\level\utils\SubChunkIteratorManager;

class Random extends Pattern
{
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator): bool
	{
		return true;
	}

	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator): ?Block
	{
		$selected = $this->pieces[array_rand($this->pieces)];
		if($selected->isValidAt($x, $y, $z, $iterator)){
			return $selected->getFor($x, $y, $z, $iterator);
		}
		return null;
	}
}