<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\input\ChunkInputData;

/**
 * @internal
 */
class ThreadData
{
	/**
	 * @var QueuedEditTask[]
	 */
	private static array $tasks = [];
	private static ?QueuedEditTask $task = null;
	/**
	 * @var ChunkInputData|null
	 */
	private static ?ChunkInputData $data = null;
	private static bool $stop = false;

	/**
	 * @return QueuedEditTask|null
	 */
	public static function getNextTask(): ?QueuedEditTask
	{
		return array_shift(self::$tasks);
	}

	/**
	 * @return QueuedEditTask|null
	 */
	public static function getTask(): ?QueuedEditTask
	{
		return self::$task;
	}

	/**
	 * @return int
	 */
	public static function getQueueLength(): int
	{
		return count(self::$tasks);
	}

	/**
	 * @param QueuedEditTask $task
	 */
	public static function addTask(QueuedEditTask $task): void
	{
		self::$tasks[] = $task;
	}

	/**
	 * @param QueuedEditTask|null $task
	 */
	public static function setTask(?QueuedEditTask $task): void
	{
		self::$task = $task;
	}

	/**
	 * @param ChunkInputData $data
	 */
	public static function storeData(ChunkInputData $data): void
	{
		self::$data = $data;
	}

	/**
	 * @return ChunkInputData|null
	 */
	public static function getStoredData(): ?ChunkInputData
	{
		$data = self::$data;
		self::$data = null;
		return $data;
	}

	public static function requirePause(): void
	{
		self::$stop = true;
	}

	/**
	 * @return bool
	 */
	public static function canExecute(): bool
	{
		$data = self::$stop;
		self::$stop = false;
		return !$data;
	}
}