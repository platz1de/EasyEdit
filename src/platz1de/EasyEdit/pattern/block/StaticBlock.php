<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\Block;
use pocketmine\level\utils\SubChunkIteratorManager;

class StaticBlock extends Pattern
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
	 * @param Selection               $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		return $this->args[0];
	}
}