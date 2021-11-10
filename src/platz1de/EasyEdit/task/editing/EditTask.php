<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\HeightMapCache;
use ThreadedLogger;

abstract class EditTask extends ExecutableTask
{
	protected string $owner;
	protected string $world;
	protected AdditionalDataManager $data;

	/**
	 * EditTask constructor.
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 */
	public function __construct(string $owner, string $world, AdditionalDataManager $data)
	{
		EditThread::getInstance()->setStatus(EditThread::STATUS_PREPARING);
		$this->owner = $owner;
		$this->world = $world;
		$this->data = $data;
	}

	/**
	 * @param int[] $chunks
	 */
	public function requestChunks(array $chunks): bool
	{
		ChunkRequestData::from($chunks, $this->world);
		ThreadData::getStoredData(); //clear chunk cache
		while (ThreadData::canExecute()) {
			if ($this->checkData()) {
				return true;
			}
			EditThread::getInstance()->waitForData();
		}
		$this->forceStop();
		return false;
	}

	public function checkData(): bool
	{
		$data = ThreadData::getStoredData();
		if ($data !== null) {
			$this->run($data);
			return true;
		}
		return false;
	}

	public function run(ChunkInputData $chunkData): void
	{
		$start = microtime(true);

		$handler = EditTaskHandler::fromData($this->world, $chunkData->getChunkData(), $chunkData->getTileData(), $this->getUndoBlockList(), $this->data);

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " loaded " . $handler->getChunkCount() . " Chunks");

		HeightMapCache::prepare();

		$this->executeEdit($handler);
		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $handler->getChangedBlockCount() . " blocks (" . $handler->getReadBlockCount() . " read, " . $handler->getWrittenBlockCount() . " written, " . $handler->getChangedTileCount() . " affected tiles)");

		if ($this->data->isSavingUndo()) {
			StorageModule::collect($handler->getChanges());
		}
		EditTaskResultCache::from(microtime(true) - $start, $handler->getChangedBlockCount());

		//foreach ($handler->getResult()->getChunks() as $hash => $chunk) {
		//	World::getXZ($hash, $x, $z);
		//	//separate chunks which are only loaded for patterns
		//	if ($this->selection->isChunkOfSelection($x, $z, $this->place)) {
		//		$result->addChunk($x, $z, $chunk);
		//	}
		//}

		//TODO: filter pattern chunks
		if ($this->data->isSavingChunks()) {
			ResultingChunkData::from($this->world, $handler->getResult()->getChunks(), $handler->getTiles());
		}

		if ($this->data->isFinalPiece()) {
			$changeId = $this->data->isSavingUndo() ? StorageModule::finishCollecting() : -1;
			TaskResultData::from($this->owner, static::class, EditTaskResultCache::getTime(), EditTaskResultCache::getChanged(), $this->data, $changeId);
		}
	}

	public function forceStop(): void
	{
		if (!$this->data->isFirstPiece()) {
			$changeId = $this->data->isSavingUndo() ? StorageModule::finishCollecting() : -1;
			TaskResultData::from($this->owner, static::class, EditTaskResultCache::getTime(), EditTaskResultCache::getChanged(), $this->data, $changeId);
		}
	}

	/**
	 * @return ThreadedLogger
	 */
	public function getLogger(): ThreadedLogger
	{
		return EditThread::getInstance()->getLogger();
	}

	/**
	 * @param EditTaskHandler $handler
	 */
	abstract public function executeEdit(EditTaskHandler $handler): void;

	/**
	 * @param EditTaskResultCache $result
	 */
	public function handleResult(EditTaskResultCache $result): void { }

	/**
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(): BlockListSelection;
}