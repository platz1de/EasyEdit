<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\task\ExecutableTask;
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
			/** @var ExecutableTask<TaskResult> $task */
			$task = $this->queue->dequeue();
			EditHandler::callback($task->getTaskId(), $task->runInternal()->getRawPayload());
			$this->lastTick = Server::getInstance()->getTick();
		}
	}

	/**
	 * @param ExecutableTask<TaskResult> $task
	 */
	public function enqueueTask(ExecutableTask $task): void
	{
		if ($this->queue->isEmpty() && Server::getInstance()->getTick() !== $this->lastTick) {
			EditHandler::callback($task->getTaskId(), $task->runInternal()->getRawPayload());
			$this->lastTick = Server::getInstance()->getTick();
		} else {
			$this->queue->enqueue($task);
		}
	}
}