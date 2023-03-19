<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\world\ChunkInformation;

abstract class GroupedChunkHandler implements ChunkHandler
{
	/**
	 * @param string $world
	 */
	public function __construct(protected string $world) {}

	abstract public function getNextChunk(): ?int;

	/**
	 * @return ChunkInformation[]
	 */
	abstract public function getData(): array;
}