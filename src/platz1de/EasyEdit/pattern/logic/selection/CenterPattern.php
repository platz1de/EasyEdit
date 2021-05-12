<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class CenterPattern extends Pattern
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
		$min = TaskCache::getFullSelection()->getCubicStart();
		$max = $min->add(TaskCache::getFullSelection()->getRealSize())->subtract(1, 1, 1);

		$xPos = ($min->getX() + $max->getX()) / 2;
		$yPos = ($min->getY() + $max->getY()) / 2;
		$zPos = ($min->getZ() + $max->getZ()) / 2;

		return floor($xPos) <= $x && $x <= ceil($xPos) && floor($yPos) <= $y && $y <= ceil($yPos) && floor($zPos) <= $z && $z <= ceil($zPos);
	}
}