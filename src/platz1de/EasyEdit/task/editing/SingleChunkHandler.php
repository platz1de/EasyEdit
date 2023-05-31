<?php

namespace platz1de\EasyEdit\task\editing;

use BadMethodCallException;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\world\ChunkInformation;

class SingleChunkHandler extends GroupedChunkHandler
{
	/**
	 * @var array<int, ChunkInformation>
	 */
	private array $chunks = [];

	/**
	 * @param int $chunk
	 */
	public function request(int $chunk): void
	{
		ChunkRequestManager::addRequest(new ChunkRequest($this->world, $chunk));
	}

	/**
	 * @param int                $chunk
	 * @param ShapeConstructor[] $constructors
	 * @return bool
	 */
	public function shouldRequest(int $chunk, array $constructors): bool
	{
		foreach ($constructors as $constructor) {
			if ($constructor->needsChunk($chunk)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param int              $chunk
	 * @param ChunkInformation $data
	 * @param int|null         $payload
	 */
	public function handleInput(int $chunk, ChunkInformation $data, ?int $payload): void
	{
		$this->chunks[$chunk] = $data;
	}

	public function clear(): void
	{
		$this->chunks = [];
	}

	/**
	 * @return int|null
	 */
	public function getNextChunk(): ?int
	{
		return array_key_first($this->chunks);
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getData(): array
	{
		if (($key = $this->getNextChunk()) === null) {
			throw new BadMethodCallException("No chunk available");
		}
		ChunkRequestManager::markAsDone();
		$ret = $this->chunks[$key];
		unset($this->chunks[$key]);
		return [$key => $ret];
	}
}