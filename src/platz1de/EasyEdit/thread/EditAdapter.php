<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\thread\chunk\ChunkRequestExecutor;
use pocketmine\scheduler\Task;

class EditAdapter extends Task
{
	public function onRun(): void
	{
		ChunkRequestExecutor::getInstance()->doTick();
		EditThread::getInstance()->parseOutput();
	}
}