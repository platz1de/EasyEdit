<?php

namespace platz1de\EasyEdit\thread\input\task;

use Closure;
use platz1de\EasyEdit\cache\TaskCache;
use platz1de\EasyEdit\task\editing\selection\SelectionEditTask;
use platz1de\EasyEdit\task\ExecutableTask;
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
		$name = "unknown";
		$id = -1;
		$player = "EasyEdit";
		$totalPieces = -1;
		$piecesLeft = -1;
		if ($task instanceof ExecutableTask) {
			$name = $task->getTaskName();
			$id = $task->getTaskId();
			$player = $task->getOwner();
			$totalPieces = 1;
			$piecesLeft = 1;
			if ($task instanceof SelectionEditTask) {
				$totalPieces = $task->getTotalPieces();
				$piecesLeft = $task->getPiecesLeft();
			}
		}
		StatsCollectResult::from($this->cacheId, $name, $id, $player, $totalPieces, $piecesLeft, ThreadData::getQueueLength());
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