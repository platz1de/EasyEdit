<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class ExecutableTask
{
	private int $taskId;

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
		$type = $stream->getString();
		/** @var ExecutableTask $task */
		$task = new $type();
		$task->parseData($stream);
		$task->taskId = EditAdapter::getId();
		return $task;
	}

	/**
	 * @return string
	 */
	abstract public function getTaskName(): string;

	/**
	 * @return int
	 */
	public function getTaskId(): int
	{
		return $this->taskId;
	}
}