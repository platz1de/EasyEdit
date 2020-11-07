<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use pocketmine\block\Block;
use pocketmine\level\utils\SubChunkIteratorManager;

class SetTask extends EditTask
{
	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 */
	public function execute(SubChunkIteratorManager $iterator, Selection $selection, Pattern $pattern): void
	{
		foreach ($selection->getAffectedBlocks() as $block) {
			$b = $pattern->getFor($block->getX(), $block->getY(), $block->getZ());
			if ($b instanceof Block) {
				$iterator->moveTo($block->getX(), $block->getY(), $block->getZ());
				$iterator->currentSubChunk->setBlock($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f, $b->getId(), $b->getDamage());
			}
		}
	}
}