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
use pocketmine\math\Vector3;

abstract class EditTask extends ExecutableTask
{
	protected string $world;
	private BlockListSelection $undo;

	/**
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		parent::__construct();
		$this->world = $world;
	}

	public function run(bool $fastSet, Vector3 $max, Vector3 $min, int $chunk, ChunkInformation $chunkInformation): void
	{
		$start = microtime(true);

		$manager = new ReferencedChunkManager($this->world);
		$manager->setChunk($chunk, $chunkInformation);
		if (!isset($this->undo)) {
			$this->undo = $this->getUndoBlockList();
			StorageModule::startCollecting($this->undo);
		}
		$handler = new EditTaskHandler($manager, $this->undo, $fastSet);

		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " loaded " . $handler->getChunkCount() . " Chunks; Using fast-set: " . ($fastSet ? "true" : "false"));

		HeightMapCache::prepare();

		$this->executeEdit($handler, $max, $min);
		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $handler->getChangedBlockCount() . " blocks (" . $handler->getReadBlockCount() . " read, " . $handler->getWrittenBlockCount() . " written)");

		EditTaskResultCache::from(microtime(true) - $start, $handler->getChangedBlockCount());

		$this->sendOutputPacket(new ResultingChunkData($this->world, $this->filterChunks($handler->getResult()->getChunks()), $handler->prepareAllInjectionData()));
	}

	public function finalize(): void
	{
		if (!$this->useDefaultHandler()) {
			return;
		}
		$changeId = StorageModule::finishCollecting();
		$this->sendOutputPacket(new HistoryCacheData($changeId, false));
		$this->notifyUser((string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()));
		ChunkRequestManager::clear();
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param Vector3         $min
	 * @param Vector3         $max
	 */
	abstract public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void;

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
	abstract public function getUndoBlockList(): BlockListSelection;

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
}