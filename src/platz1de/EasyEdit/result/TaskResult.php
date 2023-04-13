<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class TaskResult
{
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