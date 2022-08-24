<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use pocketmine\math\Vector3;

abstract class ExpandingTask extends EditTask
{
	protected Vector3 $start;
	private float $progress = 0; //worst case scenario
	/**
	 * @var bool[]
	 */
	private array $loadedChunks = [];
	/**
	 * @var int[]
	 */
	private array $requestedChunks = [];

	/**
	 * @param string  $world
	 * @param Vector3 $start
	 */
	public function __construct(string $world, Vector3 $start)
	{
		$this->start = $start;
		parent::__construct($world);
	}

	public function execute(): void
	{
		ChunkCollector::init($this->getWorld());
		ChunkCollector::collectInput(ChunkInputData::empty());
		$this->run(true, Vector3::zero(), Vector3::zero()); //TODO: not depend on edit task
		$this->finalize();
	}

	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getWorld(), $this->start);
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
			if (!$this->requestRuntimeChunks($handler, [$chunk])) {
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