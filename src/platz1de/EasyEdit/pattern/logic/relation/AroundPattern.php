<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class AroundPattern extends Pattern
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
		for ($i = 0; $i <= 6; $i++) {
			$check = (new Vector3($x, $y, $z))->getSide($i);
			$iterator->moveTo($check->getFloorX(), $check->getFloorY(), $check->getFloorZ());
			if (($iterator->currentSubChunk->getBlockId($check->getX() & 0x0f, $check->getY() & 0x0f, $check->getZ() & 0x0f) === $this->args[0]->getId()) && ($this->args[0]->getDamage() === -1 || $iterator->currentSubChunk->getBlockData($check->getX() & 0x0f, $check->getY() & 0x0f, $check->getZ() & 0x0f) === $this->args[0]->getDamage())) {
				return true;
			}
		}

		return false;
	}

	public function check(): void
	{
		try {
			$this->args[0] = Pattern::getBlockType($this->args[0] ?? "");
		} catch (ParseError $error) {
			throw new ParseError("Around needs a block as first Argument, " . ($this->args[0] ?? "") . " given");
		}
	}
}