<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class OutputData
{
	protected function __construct() { }

	protected function send(): void
	{
		EditThread::getInstance()->sendOutput($this);
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
		$stream->putString(static::class);
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
		$type = $stream->getString();
		/** @var OutputData $data */
		$data = new $type();
		$data->parseData($stream);
		return $data;
	}
}