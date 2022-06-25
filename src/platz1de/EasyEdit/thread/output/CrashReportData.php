<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use Throwable;

class CrashReportData extends OutputData
{
	private string $message;
	private SessionIdentifier $owner;

	/**
	 * @param Throwable         $throwable
	 * @param SessionIdentifier $owner
	 */
	public static function from(Throwable $throwable, SessionIdentifier $owner): void
	{
		$data = new self();
		$data->message = $throwable->getMessage();
		$data->owner = $owner;
		$data->send();
	}

	public function handle(): void
	{
		//TODO: handle
		if ($this->owner->isPlayer()) {
			Messages::send($this->owner->getName(), "task-crash", ["{message}" => $this->message]);
		}
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->message);
		$stream->putSessionIdentifier($this->owner);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->message = $stream->getString();
		$this->owner = $stream->getSessionIdentifier();
	}
}