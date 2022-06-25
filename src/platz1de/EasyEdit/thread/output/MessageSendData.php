<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class MessageSendData extends OutputData
{
	private SessionIdentifier $owner;
	private string $message;
	private bool $prefix;

	/**
	 * @param SessionIdentifier $owner
	 * @param string            $message
	 * @param bool              $prefix
	 */
	public static function from(SessionIdentifier $owner, string $message, bool $prefix = true): void
	{
		$data = new self();
		$data->owner = $owner;
		$data->message = $message;
		$data->prefix = $prefix;
		$data->send();
	}

	public function handle(): void
	{
		if ($this->owner->isPlayer()) {
			Messages::send($this->owner->getName(), $this->message, [], false, $this->prefix);
		}
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putSessionIdentifier($this->owner);
		$stream->putString($this->message);
		$stream->putBool($this->prefix);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->owner = $stream->getSessionIdentifier();
		$this->message = $stream->getString();
		$this->prefix = $stream->getBool();
	}
}