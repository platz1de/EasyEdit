<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;

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
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): ?Block
	{
		return $this->args[0];
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->args[0]->getId();
	}

	/**
	 * @return int
	 */
	public function getDamage(): int
	{
		return $this->args[0]->getDamage();
	}
}