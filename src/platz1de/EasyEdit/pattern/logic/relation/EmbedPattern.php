<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use Throwable;

class EmbedPattern extends Pattern
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
		if ($y + 1 < World::Y_MAX && $this->args->getBlock()->equals($iterator->getBlockAt($x, $y + 1, $z))) {
			return false;
		}
		foreach ((new Vector3($x, $y, $z))->sidesAroundAxis(Axis::Y) as $vector) {
			if ($y + 1 < World::Y_MAX && $this->args->getBlock()->equals($iterator->getBlockAt($vector->getFloorX(), $y + 1, $vector->getFloorZ()))) {
				return true;
			}
		}
		return false;
	}

	public function check(): void
	{
		try {
			//shut up phpstorm
			$this->args->setBlock($this->args->getBlock());
		} catch (Throwable) {
			throw new WrongPatternUsageException("Below needs a block as first Argument");
		}
	}
}