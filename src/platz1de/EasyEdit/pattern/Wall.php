<?php

namespace platz1de\EasyEdit\pattern;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class Wall extends Pattern
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
		$min = $selection->getCubicStart();
		$max = $min->add($selection->getRealSize())->subtract(1, 1, 1);
		//TODO: Non-Cubic Selections need unique checks
		return $x === $min->getX() || $x === $max->getX() || $z === $min->getZ() || $z === $max->getZ();
	}
}