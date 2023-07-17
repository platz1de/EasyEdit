<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskResultData extends OutputData
{
	public function __construct(int $taskId, private string $payload)
	{
		$this->setTaskId($taskId);
	}

	public function handle(): void
	{
		EditHandler::callback($this->getTaskId(), $this->payload);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->payload);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->payload = $stream->getString();
	}
}