<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
abstract class SelectionEditTask extends ExecutableTask
{
	protected SelectionContext $context;
	protected BlockListSelection $undo;
	protected EditTaskHandler $handler;
	private int $totalChunks;
	private int $chunksLeft;
	/**
	 * @var string
	 */
	protected string $world;
	/**
	 * @var ShapeConstructor[]
	 */
	private array $constructors;

	/**
	 * @param Selection             $selection
	 * @param SelectionContext|null $context
	 */
	public function __construct(protected Selection $selection, ?SelectionContext $context = null)
	{
		$this->context = $context ?? SelectionContext::full();
		$this->world = $selection->getWorldName();
		parent::__construct();
	}

	/**
	 * @return EditTaskResult
	 * @throws CancelException
	 */
	public function executeInternal(): EditTaskResult
	{
		$handler = $this->getChunkHandler();
		ChunkRequestManager::setHandler($handler);
		$chunks = $this->sortChunks($this->selection->getNeededChunks());
		$this->totalChunks = count($chunks);
		$this->chunksLeft = count($chunks);
		$fastSet = $this->selection->getSize()->volume() < ConfigManager::getFastSetMax();
		$this->undo = $this->createUndoBlockList();
		$this->handler = new EditTaskHandler($this->world, $this->undo, $fastSet);
		$this->constructors = iterator_to_array($this->prepareConstructors($this->handler), false);
		$skipped = 0;
		foreach ($chunks as $chunk) {
			if ($handler->shouldRequest($chunk, $this->constructors)) {
				$handler->request($chunk);
			} else {
				$skipped++;
			}
		}
		$this->totalChunks -= $skipped;
		$this->chunksLeft -= $skipped;
		if ($skipped > 0) {
			EditThread::getInstance()->debug("Skipped " . $skipped . " chunks");
		}
		while (true) {
			EditThread::getInstance()->checkExecution();
			if (($key = $handler->getNextChunk()) !== null) {
				$this->chunksLeft--;

				foreach ($handler->getData() as $k => $information) {
					$this->handler->setChunk($k, $information);
				}

				HeightMapCache::prepare();

				$this->executeEdit($this->handler, $key);
				EditThread::getInstance()->debug("Chunk " . $key . " was edited successful, " . $this->chunksLeft . " chunks left");

				$this->handler->finish();
			}
			if ($this->chunksLeft <= 0) {
				break;
			}
			if ($handler->getNextChunk() === null) {
				EditThread::getInstance()->waitForData();
			} else {
				EditThread::getInstance()->parseInput();
			}
		}

		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful, changing " . $this->handler->getChangedBlockCount() . " blocks (" . $this->handler->getReadBlockCount() . " read, " . $this->handler->getWrittenBlockCount() . " written)");
		return $this->toTaskResult();
	}

	protected function toTaskResult(): EditTaskResult
	{
		return new EditTaskResult($this->handler->getChangedBlockCount(), StorageModule::store($this->undo));
	}

	public function attemptRecovery(): EditTaskResult
	{
		return $this->toTaskResult();
	}

	/**
	 * @return BlockListSelection
	 */
	abstract public function createUndoBlockList(): BlockListSelection;

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	abstract public function prepareConstructors(EditTaskHandler $handler): Generator;

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		foreach ($this->constructors as $constructor) {
			$constructor->moveTo($chunk);
		}
	}

	/**
	 * @param int[] $chunks
	 * @return int[]
	 */
	protected function sortChunks(array $chunks): array
	{
		return $chunks;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->selection->fastSerialize());
		$stream->putString($this->context->fastSerialize());
		$stream->putString($this->world);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->selection = Selection::fastDeserialize($stream->getString());
		$this->context = SelectionContext::fastDeserialize($stream->getString());
		$this->world = $stream->getString();
	}

	/**
	 * @return Selection
	 */
	public function getSelection(): Selection
	{
		return $this->selection;
	}

	/**
	 * @return GroupedChunkHandler
	 */
	public function getChunkHandler(): GroupedChunkHandler
	{
		return new SingleChunkHandler($this->getWorld());
	}

	public function getProgress(): float
	{
		return ($this->totalChunks - $this->chunksLeft) / $this->totalChunks;
	}

	/**
	 * @return string
	 */
	public function getWorld(): string
	{
		return $this->world;
	}
}