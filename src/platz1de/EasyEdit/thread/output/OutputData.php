<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use UnexpectedValueException;

abstract class OutputData
{
	private int $taskId = -1;

	/**
	 * @return int
	 */
	public function getTaskId(): int
	{
		if($this->taskId === -1) {
			throw new UnexpectedValueException("OutputData has not been sent by a task");
		}
		return $this->taskId;
	}

	/**
	 * @param int $taskId
	 */
	public function setTaskId(int $taskId): void
	{
		$this->taskId = $taskId;
	}

	abstract public function handle(): void;

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
		$this->putData($stream);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return OutputData
	 */
	public static function fastDeserialize(string $data): OutputData
	{
		$stream = new ExtendedBinaryStream($data);
		/** @var OutputData $instance */
		$instance = igbinary_unserialize($stream->getString());
		$instance->parseData($stream);
		return $instance;
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
}