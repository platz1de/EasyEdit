<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use Throwable;

class CrashReportData extends SessionOutputData
{
	private string $message;

	/**
	 * @param Throwable $throwable
	 */
	public function __construct(Throwable $throwable)
	{
		$this->message = $throwable->getMessage();
	}

	public function handleSession(Session $session): void
	{
		//TODO: handle
		$session->sendMessage("task-crash", ["{message}" => $this->message]);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->message);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->message = $stream->getString();
	}
}