<?php

namespace platz1de\EasyEdit\worker;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class WorkerAdapter extends Task
{
	/**
	 * @var EditTask[]
	 */
	private static $tasks = [];

	/**
	 * @var int
	 */
	private static $id = 0;

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick): void
	{
		foreach (self::$tasks as $task) {
			if ($task->isFinished()) {
				$result = $task->getResult();
				if ($result instanceof ReferencedChunkManager) {
					foreach ($result->getChunks() as $chunk) {
						$result->getLevel()->setChunk($chunk->getX(), $chunk->getZ(), $chunk, false);
					}
				}
				unset(self::$tasks[$task->getId()]);
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
	 * @param EditTask $task
	 */
	public static function submit(EditTask $task): void
	{
		self::$tasks[$task->getId()] = $task;
		EasyEdit::getWorker()->stack($task);
	}
}