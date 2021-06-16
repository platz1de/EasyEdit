<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use pocketmine\level\Level;
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
			$checkX = (int) $check->getX();
			$checkY = (int) $check->getY();
			$checkZ = (int) $check->getZ();
			if ($checkY >= 0 && $checkY < Level::Y_MAX) {
				$iterator->moveTo($checkX, $checkY, $checkZ);
				if (($iterator->currentSubChunk->getBlockId($checkX & 0x0f, $checkY & 0x0f, $checkZ & 0x0f) === $this->args[0]->getId()) && ($this->args[0]->getDamage() === -1 || $iterator->currentSubChunk->getBlockData($checkX & 0x0f, $checkY & 0x0f, $checkZ & 0x0f) === $this->args[0]->getDamage())) {
					return true;
				}
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