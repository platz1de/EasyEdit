<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ChunkInputData extends InputData
{
	private string $chunkData;

	/**
	 * @param string $chunkData
	 */
	public static function from(string $chunkData): void
	{
		$data = new self();
		$data->chunkData = $chunkData;
		$data->send();
	}

	/**
	 * @return ChunkInputData
	 */
	public static function empty(): ChunkInputData
	{
		$data = new self();
		$data->chunkData = "";
		return $data;
	}

	public function handle(): void
	{
		ChunkCollector::collectInput($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->chunkData);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->chunkData = $stream->getString();
	}

	/**
	 * @return string
	 */
	public function getChunkData(): string
	{
		return $this->chunkData;
	}
}