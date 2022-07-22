<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class MessageSendData extends SessionOutputData
{
	private string $key;
	/**
	 * @var string[]
	 */
	private array $args;

	/**
	 * @param string   $key
	 * @param string[] $args
	 */
	public function __construct(string $key, array $args = [])
	{
		$this->key = $key;
		$this->args = $args;
	}

	public function handleSession(Session $session): void
	{
		$session->sendMessage($this->key, $this->args);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->key);
		$stream->putInt(count($this->args));
		foreach ($this->args as $type => $arg) {
			$stream->putString($type);
			$stream->putString($arg);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->key = $stream->getString();
		$count = $stream->getInt();
		$this->args = [];
		for ($i = 0; $i < $count; $i++) {
			$type = $stream->getString();
			$this->args[$type] = $stream->getString();
		}
	}
}