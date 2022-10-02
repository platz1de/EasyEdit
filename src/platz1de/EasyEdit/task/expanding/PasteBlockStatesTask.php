<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use pocketmine\world\World;

class PasteBlockStatesTask extends ExpandingTask
{
	use SettingNotifier;

	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @return void
	 */
	protected function run(EditTaskHandler $handler, ManagedChunkHandler $loader): void
	{
		$states = BlockStateConvertor::getAllKnownStates();
		$count = count($states);
		$x = $this->start->getFloorX();
		$y = $this->start->getFloorY();
		$z = $this->start->getFloorZ();

		$i = 0;
		foreach ($states as $id => $state) {
			$chunk = World::chunkHash(($x + floor($i / 100) * 2) >> 4, ($z + ($i % 100) * 2) >> 4);
			$this->updateProgress($i, $count);
			if (!$loader->checkRuntimeChunk($chunk)) {
				return;
			}
			$handler->changeBlock((int) ($x + floor($i / 100) * 2), $y, $z + ($i % 100) * 2, $id);
			$i++;
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}
}