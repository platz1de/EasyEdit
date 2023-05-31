<?php

namespace platz1de\EasyEdit\thread\output\result;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class TaskResultData extends OutputData
{
	public function __construct(int $taskId, private TaskResult $payload)
	{
		$this->setTaskId($taskId);
	}

	public function handle(): void
	{
		EditHandler::callback($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->payload->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->payload = TaskResult::fastDeserialize($stream->getString());
	}

	/**
	 * @return TaskResult
	 */
	public function getPayload(): TaskResult
	{
		return $this->payload;
	}
}