<?php

namespace platz1de\EasyEdit\thread;

use Closure;
use platz1de\EasyEdit\cache\ClosureCache;
use platz1de\EasyEdit\cache\HistoryCache;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\UndoTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use pocketmine\scheduler\Task;

class EditAdapter extends Task
{
	private static int $id = 0;

	public function onRun(): void
	{
		EditThread::getInstance()->parseOutput();
	}

	/**
	 * @return int
	 */
	public static function getId(): int
	{
		return ++self::$id;
	}

	/**
	 * @param QueuedEditTask $task
	 * @param Closure|null   $closure
	 */
	public static function queue(QueuedEditTask $task, ?Closure $closure): void
	{
		TaskInputData::fromTask($task);

		if ($closure === null) {
			$closure = static function (TaskResultData $result): void {
				HistoryCache::addToCache($result->getPlayer(), $result->getChangeId(), $result->getTask() === UndoTask::class);
			};
		}
		ClosureCache::addToCache($closure);
	}

	/**
	 * @return bool
	 */
	public static function cancel(): bool
	{
		//TODO
		return false;
	}

	/**
	 * @return QueuedEditTask|null
	 */
	public static function getCurrentTask(): ?QueuedEditTask
	{
		//TODO
		return null;
	}

	/**
	 * @return int
	 */
	public static function getQueueLength(): int
	{
		//TODO
		return 0;
	}
}