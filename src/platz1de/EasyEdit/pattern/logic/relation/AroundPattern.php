<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\WrongPatternUsageException;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class AroundPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		foreach((new Vector3($x, $y, $z))->sides() as $side) {
			$iterator->moveTo($side->getFloorX(), $side->getFloorY(), $side->getFloorZ());
			if ($this->args->getBlock()->equals($iterator->getCurrent()->getFullBlock($side->getX() & 0x0f, $side->getY() & 0x0f, $side->getZ() & 0x0f))) {
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
		} catch (ParseError) {
			throw new WrongPatternUsageException("Around needs a block as first Argument");
		}
	}
}