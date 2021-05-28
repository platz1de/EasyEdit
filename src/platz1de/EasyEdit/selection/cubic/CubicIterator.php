<?php

namespace platz1de\EasyEdit\selection\cubic;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

trait CubicIterator
{
	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 * @noinspection StaticClosureCanBeUsedInspection
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(function (int $x, int $y, int $z): void { }, $closure);
		$start = VectorUtils::enforceHeight($this->getCubicStart());
		$end = VectorUtils::enforceHeight($this->getCubicEnd());
		for ($x = $start->getX(); $x <= $end->getX(); $x++) {
			for ($z = $start->getZ(); $z <= $end->getZ(); $z++) {
				for ($y = $start->getY(); $y <= $end->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}
}