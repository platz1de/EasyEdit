<?php

namespace platz1de\EasyEdit\thread\output\result;

use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CancelledTaskResultData extends TaskResultData
{
	public function __construct(int $taskId, TaskResult $payload, private SessionIdentifier $reason)
	{
		parent::__construct($taskId, $payload);
	}

	public function getReason(): SessionIdentifier
	{
		return $this->reason;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->reason->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->reason = SessionIdentifier::fastDeserialize($stream->getString());
	}
}