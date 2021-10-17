<?php

namespace platz1de\EasyEdit\task\queued;

use Threaded;

class QueuedSimpleTask implements QueuedTask
{
	private Threaded $task;

	/**
	 * QueuedSimpleTask constructor.
	 * @param Threaded $task
	 */
	public function __construct(Threaded $task)
	{
		$this->task = $task;
	}

	public function isInstant(): bool
	{
		return false;
	}

	public function execute(): void
	{
		//EasyEdit::getWorker()->stack($this->task);
	}

	public function continue(): bool
	{
		return $this->task->isFinished();
	}
}