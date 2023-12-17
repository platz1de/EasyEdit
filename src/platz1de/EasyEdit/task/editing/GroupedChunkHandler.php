<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\EditThread;
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

	/**
	 * @param Selection $selection
	 * @return bool
	 */
	abstract public function checkLoaded(Selection $selection): bool;

	abstract public function getNextChunk(): ?int;

	/**
	 * @return ChunkInformation[]
	 */
	abstract public function getData(): array;

	/**
	 * @param int[]              $chunks
	 * @param ShapeConstructor[] $constructors
	 * @return int amount of requested chunks
	 */
	public function requestAll(array $chunks, array $constructors): int
	{
		$skipped = 0;
		foreach ($chunks as $chunk) {
			if ($this->shouldRequest($chunk, $constructors)) {
				$this->request($chunk);
			} else {
				$skipped++;
			}
		}
		if ($skipped > 0) {
			EditThread::getInstance()->debug("Skipped " . $skipped . " chunks");
		}
		return count($chunks) - $skipped;
	}
}