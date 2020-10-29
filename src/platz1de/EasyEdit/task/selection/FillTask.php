<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use pocketmine\level\utils\SubChunkIteratorManager;

class FillTask extends EditTask
{
	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "Fill";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 */
	public function execute(SubChunkIteratorManager $iterator, Selection $selection): void
	{
		$blocki = random_int(0, 5);
		foreach ($selection->getAffectedBlocks() as $block){
			$iterator->moveTo($block->getX(), $block->getY(), $block->getZ());
			$iterator->currentSubChunk->setBlock($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f, $blocki, 0);
		}
	}
}