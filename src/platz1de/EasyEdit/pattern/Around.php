<?php

namespace platz1de\EasyEdit\pattern;

use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class Around extends Pattern
{
	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator): bool
	{
		for ($i = 0; $i <= 6; $i++) {
			$check = (new Vector3($x, $y, $z))->getSide($i);
			if ($y >= 0 && $y < Level::Y_MAX) {
				$iterator->moveTo($check->getX(), $check->getY(), $check->getZ());
				if (($iterator->currentSubChunk->getBlockId($check->getX() & 0x0f, $check->getY() & 0x0f, $check->getZ() & 0x0f) === $this->args[0]->getId()) && ($iterator->currentSubChunk->getBlockData($check->getX() & 0x0f, $check->getY() & 0x0f, $check->getZ() & 0x0f) === $this->args[0]->getDamage())) {
					return true;
				}
			}
		}

		return false;
	}

	public function check(): void
	{
		try {
			$this->args[0] = Pattern::getBlock($this->args[0] ?? "");
		} catch (ParseError $error) {
			throw new ParseError("Around needs a block as first Argument, " . ($this->args[0] ?? "") . " given");
		}
	}
}