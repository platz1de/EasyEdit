<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskInputData extends InputData
{
	private SessionIdentifier $executor;
	private ExecutableTask $task;

	/**
	 * @param SessionIdentifier $executor
	 * @param ExecutableTask    $task
	 */
	public static function fromTask(SessionIdentifier $executor, ExecutableTask $task): void
	{
		$data = new self();
		$data->executor = $executor;
		$data->task = $task;
		$data->send();
	}

	public function handle(): void
	{
		ThreadData::addTask($this->executor, $this->task);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->executor->fastSerialize());
		$stream->putString($this->task->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->executor = SessionIdentifier::fastDeserialize($stream->getString());
		$this->task = ExecutableTask::fastDeserialize($stream->getString());
	}
}