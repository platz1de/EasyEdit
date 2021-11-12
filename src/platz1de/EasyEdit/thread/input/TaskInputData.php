<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskInputData extends InputData
{
	private ExecutableTask $task;

	/**
	 * @param ExecutableTask $task
	 */
	public static function fromTask(ExecutableTask $task): void
	{
		$data = new self();
		$data->task = $task;
		$data->send();
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
		$this->task = ExecutableTask::fastDeserialize($stream->getString());
	}
}