<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\world\ChunkInformation;
use UnexpectedValueException;

class FullChunkHandler implements ChunkHandler
{
	/**
	 * @var array<int, ChunkInformation>
	 */
	private array $chunks = [];
	private int $left = 0;

	/**
	 * @param string $world
	 */
	public function __construct(protected string $world) { }

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

	public function isDone(): bool
	{
		return $this->left === 0;
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getChunks(): array
	{
		return $this->chunks;
	}

	/**
	 * @return int[]
	 */
	public function getChunkIndexes(): array
	{
		return array_keys($this->chunks);
	}

	public function clear(): void
	{
		$this->chunks = [];
	}
}