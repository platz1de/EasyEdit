<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\session\SessionIdentifier;
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
	private static ?ExecutableTask $task = null;
	private static bool $stop = false;

	/**
	 * @return ExecutableTask[]
	 */
	public static function getNextTask(): array
	{
		//TODO: add wrapper
		return array_splice(self::$tasks, 0, 1);
	}

	/**
	 * @return ExecutableTask|null
	 */
	public static function getTask(): ?ExecutableTask
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
	 * @param SessionIdentifier $executor
	 * @param ExecutableTask    $task
	 */
	public static function addTask(SessionIdentifier $executor, ExecutableTask $task): void
	{
		self::$tasks[$executor->fastSerialize()] = $task;
	}

	/**
	 * @param ExecutableTask|null $task
	 */
	public static function setTask(?ExecutableTask $task): void
	{
		self::$task = $task;
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