<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskInputData extends InputData
{
	private QueuedEditTask $task;

	/**
	 * @param QueuedEditTask $task
	 * @return TaskInputData
	 */
	public static function fromTask(QueuedEditTask $task): TaskInputData
	{
		$data = new self();
		$data->task = $task;
		return $data;
	}

	public function handle(): void
	{
		ThreadData::addTask($this->task);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->task->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->task = QueuedEditTask::fastDeserialize($stream->getString());
	}
}