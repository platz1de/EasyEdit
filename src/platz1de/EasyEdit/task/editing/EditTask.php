<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\thread\output\HistoryCacheData;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\world\format\Chunk;

abstract class EditTask extends ExecutableTask
{
	private string $world;
	private AdditionalDataManager $data;

	/**
	 * @param EditTask              $instance
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return void
	 */
	public static function initEditTask(EditTask $instance, string $world, AdditionalDataManager $data): void
	{
		EditThread::getInstance()->setStatus(EditThread::STATUS_PREPARING);
		$instance->world = $world;
		$instance->data = $data;
	}

	/**
	 * @param int[] $chunks
	 */
	public function requestChunks(array $chunks): bool
	{
		ChunkRequestData::from($chunks, $this->world);
		ChunkCollector::clean();
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
		if (ChunkCollector::hasReceivedInput()) {
			$this->run();
			return true;
		}
		return false;
	}

	public function run(): void
	{
		$start = microtime(true);

		$handler = new EditTaskHandler(ChunkCollector::getChunks(), ChunkCollector::getTiles(), $this->getUndoBlockList());

		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " loaded " . $handler->getChunkCount() . " Chunks");

		HeightMapCache::prepare();

		$this->executeEdit($handler);
		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $handler->getChangedBlockCount() . " blocks (" . $handler->getReadBlockCount() . " read, " . $handler->getWrittenBlockCount() . " written, " . $handler->getChangedTileCount() . " affected tiles)");

		if ($this->data->isSavingUndo()) {
			StorageModule::collect($handler->getChanges());
		}
		EditTaskResultCache::from(microtime(true) - $start, $handler->getChangedBlockCount());

		if ($this->data->isSavingChunks()) {
			ResultingChunkData::from($this->world, $this->filterChunks($handler->getResult()->getChunks()), $handler->getTiles());
		}

		if ($this->data->isFinalPiece()) {
			$changeId = $this->data->isSavingUndo() ? StorageModule::finishCollecting() : -1;
			if ($this->data->hasResultHandler()) {
				$closure = $this->data->getResultHandler();
				$closure($this, $changeId);
			} else {
				HistoryCacheData::from($this->getOwner(), $changeId, false);
				/** @var class-string<EditTask> $task */
				$task = static::class;
				$task::notifyUser($this->getOwner(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $this->data);
			}
		}
	}

	public function forceStop(): void
	{
		if (!$this->data->isFirstPiece()) {
			$changeId = $this->data->isSavingUndo() ? StorageModule::finishCollecting() : -1;
			if ($this->data->hasResultHandler()) {
				$closure = $this->data->getResultHandler();
				$closure($this, $changeId);
			} else {
				HistoryCacheData::from($this->getOwner(), $changeId, false);
			}
		}
	}

	/**
	 * @param EditTaskHandler $handler
	 */
	abstract public function executeEdit(EditTaskHandler $handler): void;

	/**
	 * @param string                $player
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	abstract public static function notifyUser(string $player, string $time, string $changed, AdditionalDataManager $data): void;

	/**
	 * @param Chunk[] $chunks
	 * @return Chunk[]
	 */
	public function filterChunks(array $chunks): array
	{
		return $chunks;
	}

	/**
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(): BlockListSelection;

	/**
	 * @return string
	 */
	public function getWorld(): string
	{
		return $this->world;
	}

	/**
	 * @return AdditionalDataManager
	 */
	public function getDataManager(): AdditionalDataManager
	{
		return $this->data;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);
		$stream->putString($this->data->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();
		$this->data = AdditionalDataManager::fastDeserialize($stream->getString());
	}
}