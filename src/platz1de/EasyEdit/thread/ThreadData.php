<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\task\ExecutableTask;

/**
 * @internal
 */
class ThreadData
{
	/**
	 * @var ExecutableTask[]
	 */
	private static array $tasks = [];
	private static bool $stop = false;

	/**
	 * @return ExecutableTask|null
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
	 * @param ExecutableTask $task
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
	public static function canExecute(): bool
	{
		$data = self::$stop;
		self::$stop = false;
		return !$data;
	}
}