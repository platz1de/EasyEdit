<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class MessageSendData extends SessionOutputData
{
	private string $message;
	private bool $prefix;

	/**
	 * @param string $message
	 * @param bool   $prefix
	 */
	public function __construct(string $message, bool $prefix = true)
	{
		$this->message = $message;
		$this->prefix = $prefix;
	}

	public function handleSession(Session $session): void
	{
		Messages::send($session->getPlayer(), $this->message, [], false, $this->prefix);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->message);
		$stream->putBool($this->prefix);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->message = $stream->getString();
		$this->prefix = $stream->getBool();
	}
}