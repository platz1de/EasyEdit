<?php

namespace platz1de\EasyEdit\thread\input\task;

use Closure;
use platz1de\EasyEdit\cache\TaskCache;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\StatsCollectResult;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CollectStatsTask extends InputData
{
	private int $cacheId;

	public static function from(Closure $closure): void
	{
		$data = new self();
		$data->cacheId = TaskCache::cache($closure);
		$data->send();
	}

	public function handle(): void
	{
		$task = ThreadData::getTask();
		$name = "unknown";
		$id = -1;
		$player = "EasyEdit";
		$progress = 0;
		if ($task instanceof ExecutableTask) {
			$name = $task->getTaskName();
			$id = $task->getTaskId();
			//TODO: this all only worked due to hacks
			//$player = $task->getOwner()->isPlayer() ? $task->getOwner()->getName() : "internal";
			$progress = $task->getProgress();
		}
		StatsCollectResult::from($this->cacheId, $name, $id, $player, $progress, ThreadData::getQueueLength(), StorageModule::getSize(), memory_get_usage(), memory_get_usage(true));
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->cacheId);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->cacheId = $stream->getInt();
	}
}