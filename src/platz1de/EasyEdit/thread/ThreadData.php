<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\input\ChunkInputData;

class ThreadData
{
	/**
	 * @var QueuedEditTask[]
	 */
	private static array $tasks = [];
	/**
	 * @var ChunkInputData|null
	 */
	private static ?ChunkInputData $data = null;

	/**
	 * @return QueuedEditTask|null
	 * @internal
	 */
	public static function getNextTask(): ?QueuedEditTask
	{
		return array_shift(self::$tasks);
	}

	/**
	 * @param QueuedEditTask $task
	 * @internal
	 */
	public static function addTask(QueuedEditTask $task): void
	{
		self::$tasks[] = $task;
	}

	/**
	 * @param ChunkInputData $data
	 * @internal
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
}