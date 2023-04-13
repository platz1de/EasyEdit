<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\task\ExecutableTask;

/**
 * @internal
 */
class ThreadData
{
	/**
	 * @var ExecutableTask<TaskResult>[]
	 */
	private static array $tasks = [];
	private static bool $stop = false;

	/**
	 * @return ExecutableTask<TaskResult>|null
	 */
	public static function getNextTask(): ?ExecutableTask
	{
		return array_shift(self::$tasks);
	}

	/**
	 * @return int
	 */
	public static function getQueueLength(): int
	{
		return count(self::$tasks);
	}

	/**
	 * @param ExecutableTask<TaskResult> $task
	 */
	public static function addTask(ExecutableTask $task): void
	{
		self::$tasks[] = $task;
	}

	public static function requirePause(): void
	{
		self::$stop = true;
	}

	/**
	 * @return bool
	 */
	public static function requiresCancel(): bool
	{
		return self::$stop;
	}

	public static function clear(): void
	{
		self::$stop = false;
	}
}