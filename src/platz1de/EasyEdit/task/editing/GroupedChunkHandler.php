<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\world\ChunkInformation;

abstract class GroupedChunkHandler implements ChunkHandler
{
	/**
	 * @param string $world
	 */
	public function __construct(protected string $world) {}

	/**
	 * @param int                $chunk
	 * @param ShapeConstructor[] $constructors
	 * @return bool
	 */
	abstract public function shouldRequest(int $chunk, array $constructors): bool;

	abstract public function getNextChunk(): ?int;

	/**
	 * @return ChunkInformation[]
	 */
	abstract public function getData(): array;
}