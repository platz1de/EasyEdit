<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\world\ChunkInformation;

interface ChunkHandler
{
	public function request(int $chunk): bool;

	/**
	 * @param array<int, ChunkInformation> $chunks
	 */
	public function handleInput(array $chunks): void;

	public function clear(): void;
}