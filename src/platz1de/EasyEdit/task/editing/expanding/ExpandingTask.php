<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\input\ChunkInputData;

abstract class ExpandingTask extends EditTask
{
	private float $progress = 0; //worst case scenario
	/**
	 * @var bool[]
	 */
	private array $loadedChunks = [];
	/**
	 * @var int[]
	 */
	private array $requestedChunks = [];

	public function execute(): void
	{
		$this->getDataManager()->useFastSet();
		$this->getDataManager()->setFinal();
		ChunkCollector::init($this->getWorld());
		ChunkCollector::collectInput(ChunkInputData::empty());
		$this->run();
		ChunkCollector::clear();
	}

	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->getPosition());
	}

	public function getProgress(): float
	{
		return $this->progress; //Unknown
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 * @param int             $current
	 * @param int             $max
	 * @return bool
	 */
	public function checkRuntimeChunk(EditTaskHandler $handler, int $chunk, int $current, int $max): bool
	{
		if (!isset($this->loadedChunks[$chunk])) {
			$this->loadedChunks[$chunk] = true;
			$this->progress = $current / $max;
			if (!$this->requestRuntimeChunk($handler, $chunk)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param int $chunk
	 */
	public function registerRequestedChunks(int $chunk): void
	{
		if (!isset($this->requestedChunks[$chunk])) {
			$this->requestedChunks[$chunk] = 0;
		}
		$this->requestedChunks[$chunk]++;
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function checkUnload(EditTaskHandler $handler, int $chunk): void
	{
		if (isset($this->requestedChunks[$chunk]) && --$this->requestedChunks[$chunk] <= 0) {
			unset($this->requestedChunks[$chunk], $this->loadedChunks[$chunk]);
			$this->sendRuntimeChunk($handler, $chunk);
		}
	}
}