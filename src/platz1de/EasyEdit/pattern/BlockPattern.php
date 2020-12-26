<?php

namespace platz1de\EasyEdit\pattern;

use pocketmine\block\Block;
use pocketmine\level\utils\SubChunkIteratorManager;

class BlockPattern extends Pattern
{
	/**
	 * BlockPattern constructor.
	 * @param Block $block
	 */
	public function __construct(Block $block)
	{
		parent::__construct([], [$block]);
	}

	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator): ?Block
	{
		return $this->args[0];
	}

	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator): bool
	{
		return true;
	}
}