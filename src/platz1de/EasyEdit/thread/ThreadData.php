<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\ChunkInputData;

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
	/**
	 * @var ChunkInputData|null
	 */
	private static ?ChunkInputData $data = null;
	private static bool $stop = false;

	/**
	 * @return ExecutableTask|null
	 */
	public static function getNextTask(): ?ExecutableTask
	{
		return array_shift(self::$tasks);
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
	 * @param ExecutableTask $task
	 */
	public static function addTask(ExecutableTask $task): void
	{
		self::$tasks[] = $task;
	}

	/**
	 * @param ExecutableTask|null $task
	 */
	public static function setTask(?ExecutableTask $task): void
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