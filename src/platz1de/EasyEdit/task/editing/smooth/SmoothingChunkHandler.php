<?php

namespace platz1de\EasyEdit\task\editing\smooth;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\world\ChunkInformation;
use UnexpectedValueException;

/**
 * TODO: Add a limit to the amount of chunks that can be loaded at once
 * Dummy chunk handler (smoothing needs to load everything at once)
 */
class SmoothingChunkHandler extends GroupedChunkHandler
{
	/**
	 * @var ChunkInformation[]
	 */
	private array $chunks = [];
	private int $left = 0;

	/**
	 * @param int $chunk
	 */
	public function request(int $chunk): void
	{
		EasyEdit::getEnv()->processChunkRequest(new ChunkRequest($this->world, $chunk, $chunk), $this);
		EasyEdit::getEnv()->finalizeChunkStep();
		$this->left++;
	}

	/**
	 * @param int                $chunk
	 * @param ShapeConstructor[] $constructors
	 * @return bool
	 */
	public function shouldRequest(int $chunk, array $constructors): bool
	{
		return true;
	}

	/**
	 * @param int              $chunk
	 * @param ChunkInformation $data
	 * @param int|null         $payload
	 */
	public function handleInput(int $chunk, ChunkInformation $data, ?int $payload): void
	{
		if ($payload === null) {
			throw new UnexpectedValueException("Payload is null");
		}
		$this->chunks[$payload] = $data;
		$this->left--;
	}

	public function clear(): void
	{
		$this->chunks = [];
		$this->left = 0;
	}

	/**
	 * @return int|null
	 */
	public function getNextChunk(): ?int
	{
		if ($this->left === -1) {
			return -1;
		}
		if ($this->left !== 0) {
			return null;
		}
		return 0;
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getData(): array
	{
		if ($this->left !== 0) {
			return [];
		}
		$this->left = -1;
		return $this->chunks;
	}
}