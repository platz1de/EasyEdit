<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskNotifyData extends OutputData
{
	public function __construct(private int $value) {}

	public function handle(): void
	{
		EditHandler::notify($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->value);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->value = $stream->getInt();
	}

	/**
	 * @return int
	 */
	public function getPayload(): int
	{
		return $this->value;
	}
}