<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\world\ChunkInformation;

interface ChunkHandler
{
	public function request(int $chunk): void;

	/**
	 * @param int              $chunk
	 * @param ChunkInformation $data
	 * @param int|null         $payload
	 */
	public function handleInput(int $chunk, ChunkInformation $data, ?int $payload): void;

	public function clear(): void;
}