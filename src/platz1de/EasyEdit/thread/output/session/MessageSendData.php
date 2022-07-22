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
	private bool $prefix;

	/**
	 * @param string   $key
	 * @param string[] $args
	 * @param bool     $prefix
	 */
	public function __construct(string $key, array $args = [], bool $prefix = true)
	{
		$this->key = $key;
		$this->args = $args;
		$this->prefix = $prefix;
	}

	public function handleSession(Session $session): void
	{
		$session->sendMessage($this->key, $this->args, $this->prefix);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->key);
		$stream->putInt(count($this->args));
		foreach ($this->args as $type => $arg) {
			$stream->putString($type);
			$stream->putString($arg);
		}
		$stream->putBool($this->prefix);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->key = $stream->getString();
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$type = $stream->getString();
			$this->args[$type] = $stream->getString();
		}
		$this->prefix = $stream->getBool();
	}
}