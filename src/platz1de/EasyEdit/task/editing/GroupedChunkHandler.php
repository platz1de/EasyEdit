<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\world\ChunkInformation;

abstract class GroupedChunkHandler implements ChunkHandler
{
	protected string $world;

	/**
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		$this->world = $world;
	}

	abstract public function getNextChunk(): ?int;

	/**
	 * @return ChunkInformation[]
	 */
	abstract public function getData(): array;
}