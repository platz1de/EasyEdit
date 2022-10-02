<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\world\ChunkInformation;

class SingleChunkHandler implements ChunkHandler
{
	/**
	 * @var ChunkInformation[]
	 */
	private array $chunks = [];
	private string $world;

	/**
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		$this->world = $world;
	}

	/**
	 * @param int $chunk
	 * @return true
	 */
	public function request(int $chunk): bool
	{
		ChunkRequestManager::addRequest(new ChunkRequest($this->world, $chunk));
		return true;
	}

	/**
	 * @param ChunkInformation[] $chunks
	 */
	public function handleInput(array $chunks): void
	{
		foreach ($chunks as $index => $chunk) {
			$this->chunks[$index] = $chunk;
		}
	}

	public function clear(): void
	{
		$this->chunks = [];
	}

	/**
	 * @return int|null
	 */
	public function getKey(): ?int
	{
		return array_key_first($this->chunks);
	}

	/**
	 * @return ChunkInformation|null
	 */
	public function getNext(): ?ChunkInformation
	{
		if (($key = $this->getKey()) === null) {
			return null;
		}
		ChunkRequestManager::markAsDone();
		$ret = $this->chunks[$key];
		unset($this->chunks[$key]);
		return $ret;
	}
}