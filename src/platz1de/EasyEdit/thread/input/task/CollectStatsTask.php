<?php

namespace platz1de\EasyEdit\thread\input\task;

use Closure;
use platz1de\EasyEdit\cache\TaskCache;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\input\InputData;
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
		if ($task instanceof QueuedEditTask) {
			$piece = $task->getCurrentPiece();
			StatsCollectResult::from($this->cacheId, $piece->getTaskName(), $piece->getId(), $task->getSelection()->getPlayer(), $task->getTotalLength(), $task->getLength(), ThreadData::getQueueLength());
		} else {
			StatsCollectResult::from($this->cacheId, "unknown", -1, "EasyEdit", -1, -1, ThreadData::getQueueLength());
		}
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