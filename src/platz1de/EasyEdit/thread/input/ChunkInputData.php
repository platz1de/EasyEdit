<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ChunkInputData extends InputData
{
	private string $chunkData;
	private ?int $payload;

	/**
	 * @param string   $chunkData
	 * @param int|null $payload
	 */
	public static function from(string $chunkData, ?int $payload): void
	{
		$data = new self();
		$data->chunkData = $chunkData;
		$data->payload = $payload;
		$data->send();
	}

	public function handle(): void
	{
		ChunkRequestManager::handleInput($this->chunkData, $this->payload);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->chunkData);
		$stream->putBool($this->payload !== null);
		if ($this->payload !== null) {
			$stream->putInt($this->payload);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->chunkData = $stream->getString();
		$this->payload = $stream->getBool() ? $stream->getInt() : null;
	}
}