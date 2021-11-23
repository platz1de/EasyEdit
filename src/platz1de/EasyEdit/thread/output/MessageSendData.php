<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class MessageSendData extends OutputData
{
	private string $player;
	private string $message;
	private bool $prefix;

	/**
	 * @param string $player
	 * @param string $message
	 * @param bool   $prefix
	 */
	public static function from(string $player, string $message, bool $prefix = true): void
	{
		$data = new self();
		$data->player = $player;
		$data->message = $message;
		$data->prefix = $prefix;
		$data->send();
	}

	public function handle(): void
	{
		Messages::send($this->player, $this->message, [], false, $this->prefix);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->player);
		$stream->putString($this->message);
		$stream->putBool($this->prefix);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->message = $stream->getString();
		$this->prefix = $stream->getBool();
	}
}