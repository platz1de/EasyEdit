<?php

namespace platz1de\EasyEdit\worker;

use platz1de\EasyEdit\task\CallbackTask;
use platz1de\EasyEdit\task\PieceManager;
use platz1de\EasyEdit\task\QueuedTask;
use pocketmine\scheduler\Task;

class WorkerAdapter extends Task
{
	/**
	 * @var PieceManager|null
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

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick): void
	{
		if (count(self::$priority) > 0) {
			$task = array_shift(self::$priority);
			if ($task instanceof CallbackTask) {
				$task->callback();
			} else {
				array_unshift(self::$queue, self::$task);
				self::$task = new PieceManager($task);
				self::$task->start(); //This can create a stack greater than 1 on worker
			}
		}

		if (self::$task !== null) {
			if (self::$task->continue()) {
				self::$task = null;
			} else {
				return;
			}
		}

		if (count(self::$queue) > 0) {
			$task = array_shift(self::$queue);
			if ($task instanceof CallbackTask) {
				$task->callback();
			} else {
				self::$task = new PieceManager($task);
				self::$task->start();
			}
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
	 * @return PieceManager|null
	 */
	public static function getCurrentTask(): ?PieceManager
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