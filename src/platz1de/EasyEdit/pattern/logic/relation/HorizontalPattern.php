<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use Throwable;

class HorizontalPattern extends Pattern
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
		foreach ((new Vector3($x, $y, $z))->sidesAroundAxis(Axis::Y) as $side) {
			if ($this->args->getBlock()->equals($iterator->getBlock($side))) {
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
			throw new WrongPatternUsageException("Around needs a block as first Argument");
		}
	}
}