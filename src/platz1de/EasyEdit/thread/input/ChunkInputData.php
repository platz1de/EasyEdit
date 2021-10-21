<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ChunkInputData extends InputData
{
	private string $chunkData;
	private string $tileData;

	/**
	 * @param string $chunkData
	 * @param string $tileData
	 */
	public static function from(string $chunkData, string $tileData): void
	{
		$data = new self();
		$data->chunkData = $chunkData;
		$data->tileData = $tileData;
		$data->send();
	}

	public function handle(): void
	{
		ThreadData::storeData($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->chunkData);
		$stream->putString($this->tileData);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->chunkData = $stream->getString();
		$this->tileData = $stream->getString();
	}

	/**
	 * @return string
	 */
	public function getChunkData(): string
	{
		return $this->chunkData;
	}

	/**
	 * @return string
	 */
	public function getTileData(): string
	{
		return $this->tileData;
	}
}