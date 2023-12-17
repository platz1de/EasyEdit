<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use SplQueue;

class MainThreadTaskScheduler
{
	use SingletonTrait;

	/**
	 * @var SplQueue<ExecutableTask<TaskResult>>
	 */
	private SplQueue $queue;
	private int $lastTick = 0;

	public function __construct()
	{
		$this->queue = new SplQueue();
	}

	public function tick(): void
	{
		if (!$this->queue->isEmpty()) {
			$this->executeTask($this->queue->dequeue());
		}
	}

	/**
	 * @param ExecutableTask<TaskResult> $task
	 */
	public function enqueueTask(ExecutableTask $task): void
	{
		if ($this->queue->isEmpty() && Server::getInstance()->getTick() !== $this->lastTick) {
			$this->executeTask($task);
		} else {
			$this->queue->enqueue($task);
		}
	}

	/**
	 * @param ExecutableTask<TaskResult> $task
	 */
	private function executeTask(ExecutableTask $task): void
	{
		if (!$task->canExecuteOnMainThread()) {
			EditThread::getInstance()->debug("Task " . $task->getTaskId() . " has unloaded chunks, moving to edit thread");
			TaskInputData::fromTask($task);
			return;
		}
		EditHandler::callback($task->getTaskId(), $task->runInternal()->getRawPayload());
		$this->lastTick = Server::getInstance()->getTick();
	}
}