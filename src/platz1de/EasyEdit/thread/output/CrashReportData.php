<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use Throwable;

class CrashReportData extends OutputData
{
	private string $message;
	private string $player;

	/**
	 * @param Throwable $throwable
	 * @param string    $player
	 */
	public static function from(Throwable $throwable, string $player): void
	{
		$data = new self();
		$data->message = $throwable->getMessage();
		$data->player = $throwable->getMessage();
		$data->send();
	}

	public function handle(): void
	{
		Messages::send($this->player, "task-crash", ["{message}" => $this->message]);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->message);
		$stream->putString($this->player);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->message = $stream->getString();
		$this->player = $stream->getString();
	}
}