<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use pocketmine\world\World;

class PasteBlockStatesTask extends ExpandingTask
{
	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @throws CancelException
	 */
	public function executeEdit(EditTaskHandler $handler, ManagedChunkHandler $loader): void
	{
		$states = BlockStateTranslationManager::requestRuntimeId([], true, true);
		$count = count($states);
		$x = $this->start->x;
		$y = $this->start->y;
		$z = $this->start->z;

		$i = 0;
		foreach ($states as $id) {
			$chunk = World::chunkHash(($x + floor($i / 100) * 2) >> 4, ($z + ($i % 100) * 2) >> 4);
			$this->updateProgress($i, $count);
			$loader->checkRuntimeChunk($chunk);
			$handler->changeBlock((int) ($x + floor($i / 100) * 2), $y, $z + ($i % 100) * 2, $id);
			$i++;
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}
}