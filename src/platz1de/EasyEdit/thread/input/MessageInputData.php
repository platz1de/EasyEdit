<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class MessageInputData extends InputData
{
	/**
	 * @var string[]
	 */
	private array $messages;

	/**
	 * @param string[] $messages
	 */
	public static function from(array $messages): void
	{
		$data = new self();
		$data->messages = $messages;
		$data->send();
	}

	public function handle(): void
	{
		Messages::setMessageData($this->messages);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->messages));
		foreach ($this->messages as $id => $message) {
			$stream->putString($id);
			$stream->putString($message);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			/** @noinspection AmbiguousMethodsCallsInArrayMappingInspection */
			$this->messages[$stream->getString()] = $stream->getString();
		}
	}
}