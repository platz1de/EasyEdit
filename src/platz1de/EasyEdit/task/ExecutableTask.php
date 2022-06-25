<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class ExecutableTask
{
	private int $taskId;
	private SessionIdentifier $owner;

	public function __construct(SessionIdentifier $owner)
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
		$stream->putString(igbinary_serialize($this) ?? "");
		$stream->putString($this->owner->fastSerialize());
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
		/** @var ExecutableTask $task */
		$task = igbinary_unserialize($stream->getString());
		$task->owner = SessionIdentifier::fastDeserialize($stream->getString());
		$task->parseData($stream);
		return $task;
	}

	/**
	 * @return array{int}
	 */
	public function __serialize(): array
	{
		return [$this->taskId];
	}

	/**
	 * @param array{int} $data
	 */
	public function __unserialize(array $data): void
	{
		$this->taskId = $data[0];
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
	 * @return SessionIdentifier
	 */
	public function getOwner(): SessionIdentifier
	{
		return $this->owner;
	}
}