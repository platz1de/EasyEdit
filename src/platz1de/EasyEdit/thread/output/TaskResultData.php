<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskResultData extends OutputData
{
	public function __construct(int $taskId, private TaskResult $payload, private bool $success, private ?string $message = null)
	{
		$this->setTaskId($taskId);
	}

	public function handle(): void
	{
		EditHandler::callback($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBool($this->success);
		$stream->putString($this->payload->fastSerialize());

		$stream->putBool($this->message !== null);
		if ($this->message !== null) {
			$stream->putString($this->message);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->success = $stream->getBool();
		$this->payload = TaskResult::fastDeserialize($stream->getString());
		$this->message = $stream->getBool() ? $stream->getString() : null;
	}

	/**
	 * @return TaskResult
	 */
	public function getPayload(): TaskResult
	{
		return $this->payload;
	}

	/**
	 * @return bool
	 */
	public function isSuccess(): bool
	{
		return $this->success;
	}

	/**
	 * @return string|null
	 */
	public function getMessage(): ?string
	{
		return $this->message;
	}
}