<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use RuntimeException;

abstract class TaskResult
{
	private float $time = -1.0;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function putData(ExtendedBinaryStream $stream): void;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function parseData(ExtendedBinaryStream $stream): void;

	public function enrichWithTime(float $time): void
	{
		$this->time = $time;
	}

	/**
	 * @Note: This only works if this class was passed from a result promise
	 * @return float
	 */
	public function getTime(): float
	{
		if ($this->time === -1.0) {
			throw new RuntimeException("Time was not set correctly!");
		}
		return $this->time;
	}

	public function getFormattedTime(): string
	{
		return (string) round($this->getTime(), 2);
	}

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
	 * @return TaskResult
	 */
	public static function fastDeserialize(string $data): TaskResult
	{
		$stream = new ExtendedBinaryStream($data);
		/** @var TaskResult $instance */
		$instance = igbinary_unserialize($stream->getString());
		$instance->parseData($stream);
		return $instance;
	}

	/**
	 * @return array{}
	 */
	public function __serialize(): array
	{
		return [];
	}

	/**
	 * @param array{int} $data
	 */
	public function __unserialize(array $data): void {}
}