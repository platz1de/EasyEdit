<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use pocketmine\scheduler\Task;

class EditAdapter extends Task
{
	public function onRun(): void
	{
		EditThread::getInstance()->parseOutput();
		BlockStateTranslationManager::tick();
	}
}