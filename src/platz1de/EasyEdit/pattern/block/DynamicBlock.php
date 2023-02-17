<?php

namespace platz1de\EasyEdit\pattern\block;

use pocketmine\block\Block;

/** Ignores Damage */
class DynamicBlock extends StaticBlock
{
	/**
	 * @return int
	 */
	public function getMeta(): int
	{
		return -1;
	}

	public function equals(int $fullBlock): bool
	{
		return ($fullBlock >> Block::INTERNAL_STATE_DATA_BITS) === $this->getTypeId();
	}
}