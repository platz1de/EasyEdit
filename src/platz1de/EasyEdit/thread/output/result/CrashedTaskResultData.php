<?php

namespace platz1de\EasyEdit\thread\output\result;

use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CrashedTaskResultData extends TaskResultData
{
	public function __construct(int $taskId, TaskResult $payload, private string $message)
	{
		parent::__construct($taskId, $payload);
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->message);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->message = $stream->getString();
	}
}