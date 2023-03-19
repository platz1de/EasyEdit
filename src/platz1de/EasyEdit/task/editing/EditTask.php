<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\output\session\HistoryCacheData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\HeightMapCache;
use platz1de\EasyEdit\world\ReferencedChunkManager;

abstract class EditTask extends ExecutableTask
{
	protected BlockListSelection $undo;
	protected EditTaskHandler $handler;
	protected float $totalTime = 0;
	protected int $totalBlocks = 0;

	/**
	 * @param string $world
	 */
	public function __construct(protected string $world)
	{
		parent::__construct();
	}

	public function prepare(bool $fastSet): void
	{
		$this->undo = $this->createUndoBlockList();
		$this->handler = new EditTaskHandler($this->undo, $fastSet);
		EditThread::getInstance()->debug("Preparing Task " . $this->getTaskName() . ":" . $this->getTaskId() . "; Using fast-set: " . ($fastSet ? "true" : "false"));
	}

	/**
	 * @param int                $chunk
	 * @param ChunkInformation[] $chunkInformation
	 */
	public function run(int $chunk, array $chunkInformation): void
	{
		$start = microtime(true);

		$manager = new ReferencedChunkManager($this->world);
		foreach ($chunkInformation as $key => $information) {
			$manager->setChunk($key, $information);
		}
		$this->handler->setManager($manager);

		HeightMapCache::prepare();

		$this->executeEdit($this->handler, $chunk);
		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $this->handler->getChangedBlockCount() . " blocks (" . $this->handler->getReadBlockCount() . " read, " . $this->handler->getWrittenBlockCount() . " written)");

		$this->totalTime += microtime(true) - $start;
		$this->totalBlocks += $this->handler->getChangedBlockCount();

		$this->sendOutputPacket(new ResultingChunkData($this->world, $this->filterChunks($this->handler->getResult()->getChunks()), $this->handler->prepareAllInjectionData()));
	}

	public function finalize(): void
	{
		if (!$this->useDefaultHandler()) {
			return;
		}
		$changeId = StorageModule::store($this->undo);
		$this->sendOutputPacket(new HistoryCacheData($changeId, false));
		$this->notifyUser((string) round($this->totalTime, 2), MixedUtils::humanReadable($this->totalBlocks));
		ChunkRequestManager::clear();
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	abstract public function executeEdit(EditTaskHandler $handler, int $chunk): void;

	/**
	 * @param string $time
	 * @param string $changed
	 */
	abstract public function notifyUser(string $time, string $changed): void;

	/**
	 * Filters actually edited chunks
	 * @param ChunkInformation[] $chunks
	 * @return ChunkInformation[]
	 */
	public function filterChunks(array $chunks): array
	{
		foreach ($chunks as $hash => $chunk) {
			if (!$chunk->wasUsed()) {
				unset($chunks[$hash]);
			}
		}
		return $chunks;
	}

	/**
	 * @return BlockListSelection
	 */
	abstract public function createUndoBlockList(): BlockListSelection;

	/**
	 * @return string
	 */
	public function getWorld(): string
	{
		return $this->world;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();
	}

	/**
	 * @return float
	 */
	public function getTotalTime(): float
	{
		return $this->totalTime;
	}

	/**
	 * @return int
	 */
	public function getTotalBlocks(): int
	{
		return $this->totalBlocks;
	}

	/**
	 * @return BlockListSelection
	 */
	public function getUndo(): BlockListSelection
	{
		return $this->undo;
	}
}