<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use pocketmine\world\World;

class PasteBlockStatesTask extends ExpandingTask
{
	use SettingNotifier;

	public function executeEdit(EditTaskHandler $handler): void
	{
		$states = BlockStateConvertor::getAllKnownStates();
		$count = count($states);
		$x = $this->start->getFloorX();
		$y = $this->start->getFloorY();
		$z = $this->start->getFloorZ();

		if (!$this->checkRuntimeChunk($handler, World::chunkHash($x, $z), 0, 1)) {
			return;
		}

		$i = 0;
		foreach ($states as $id => $state) {
			$chunk = World::chunkHash(($x + floor($i / 100) * 2) >> 4, ($z + ($i % 100) * 2) >> 4);
			if (!$this->checkRuntimeChunk($handler, $chunk, $i, $count)) {
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