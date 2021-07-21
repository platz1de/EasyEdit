<?php

namespace platz1de\EasyEdit\worker;

use platz1de\EasyEdit\task\queued\QueuedTask;
use pocketmine\scheduler\Task;

class WorkerAdapter extends Task
{
	/**
	 * @var QueuedTask|null
	 */
	private static $task;
	/**
	 * @var QueuedTask[]
	 */
	private static $queue = [];
	/**
	 * @var QueuedTask[]
	 */
	private static $priority = [];

	/**
	 * @var int
	 */
	private static $id = 0;

	public function onRun(): void
	{
		if (count(self::$priority) > 0) {
			/** @var QueuedTask $task */
			$task = array_shift(self::$priority);
			if (!$task->isInstant()) {
				if (self::$task !== null) {
					array_unshift(self::$queue, self::$task);
				}
				self::$task = $task;
			}
			$task->execute(); //This can create a stack greater than 1 on worker
		} elseif (self::$task !== null) {
			if (self::$task->continue()) {
				self::$task = null;
			}
		} elseif (count(self::$queue) > 0) {
			/** @var QueuedTask $task */
			$task = array_shift(self::$queue);
			if (!$task->isInstant()) {
				self::$task = $task;
			}
			$task->execute();
		}
	}

	/**
	 * @return int
	 */
	public static function getId(): int
	{
		return ++self::$id;
	}

	/**
	 * Skip queue
	 * @param QueuedTask $task
	 */
	public static function priority(QueuedTask $task): void
	{
		self::$priority[] = $task;
	}

	/**
	 * @param QueuedTask $task
	 */
	public static function queue(QueuedTask $task): void
	{
		self::$queue[] = $task;
	}

	/**
	 * @return bool
	 */
	public static function cancel(): bool
	{
		//This only cancels future piece executions NOT the current one
		$existed = self::$task !== null;
		self::$task = null;
		return $existed;
	}

	/**
	 * @return QueuedTask|null
	 */
	public static function getCurrentTask(): ?QueuedTask
	{
		return self::$task ?? null;
	}

	/**
	 * @return int
	 */
	public static function getQueueLength(): int
	{
		return count(self::$queue);
	}
}