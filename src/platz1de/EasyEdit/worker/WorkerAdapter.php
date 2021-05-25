<?php

namespace platz1de\EasyEdit\worker;

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
	 * @var int
	 */
	private static $id = 0;

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick): void
	{
		if (self::$task !== null) {
			if (self::$task->continue()) {
				self::$task = null;
			} else {
				return;
			}
		}

		if (count(self::$queue) > 0) {
			self::$task = new PieceManager(array_shift(self::$queue));
			self::$task->start();
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
	 * @param QueuedTask $task
	 */
	public static function queue(QueuedTask $task): void
	{
		self::$queue[] = $task;
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