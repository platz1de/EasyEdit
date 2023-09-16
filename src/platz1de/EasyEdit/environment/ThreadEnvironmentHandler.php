<?php

namespace platz1de\EasyEdit\environment;

use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ChunkInformation;

abstract class ThreadEnvironmentHandler
{
	abstract public function submitResultingChunks(ChunkController $controller): void;

	/**
	 * @param string           $world
	 * @param int              $index
	 * @param ChunkInformation $chunk
	 * @param string[]         $injections
	 */
	abstract public function submitSingleChunk(string $world, int $index, ChunkInformation $chunk, array $injections): void;

	abstract public function initChunkHandler(ChunkHandler $handler): void;

	abstract public function processChunkRequest(ChunkRequest $chunk, ChunkHandler $handler): void;

	abstract public function finalizeChunkStep(): void;

	abstract public function postProgress(float $progress): void;
}