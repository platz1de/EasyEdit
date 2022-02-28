<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class ExecutableTask
{
	private int $taskId;
	private string $owner;

	public function __construct(string $owner)
	{
		$this->taskId = EditAdapter::getId();
		$this->owner = $owner;
	}

	abstract public function execute(): void;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function putData(ExtendedBinaryStream $stream): void;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function parseData(ExtendedBinaryStream $stream): void;

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString(static::class);
		$stream->putString($this->owner);
		$this->putData($stream);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return ExecutableTask
	 */
	public static function fastDeserialize(string $data): ExecutableTask
	{
		$stream = new ExtendedBinaryStream($data);
		/** @phpstan-var class-string<ExecutableTask> $type */
		$type = $stream->getString();
		/** @var ExecutableTask $task */
		$task = new $type($stream->getString());
		$task->parseData($stream);
		return $task;
	}

	/**
	 * @return string
	 */
	abstract public function getTaskName(): string;

	/**
	 * @return float
	 */
	abstract public function getProgress(): float;

	/**
	 * @return int
	 */
	public function getTaskId(): int
	{
		return $this->taskId;
	}

	/**
	 * @return string
	 */
	public function getOwner(): string
	{
		return $this->owner;
	}
}