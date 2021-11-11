<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class MessageSendData extends OutputData
{
	private string $player;
	private string $message;

	/**
	 * @param string $player
	 * @param string $message
	 */
	public static function from(string $player, string $message): void
	{
		$data = new self();
		$data->player = $player;
		$data->message = $message;
		$data->send();
	}

	public function handle(): void
	{
		Messages::send($this->player, $this->message, [], false);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->player);
		$stream->putString($this->message);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->message = $stream->getString();
	}
}