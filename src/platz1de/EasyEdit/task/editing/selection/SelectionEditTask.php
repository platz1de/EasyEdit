<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\task\editing\SingleChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;

abstract class SelectionEditTask extends EditTask
{
	protected SelectionContext $context;
	private int $totalChunks;
	private int $chunksLeft;
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
		parent::__construct($selection->getWorldName());
	}

	public function execute(): void
	{
		$handler = $this->getChunkHandler();
		ChunkRequestManager::setHandler($handler);
		$chunks = $this->sortChunks($this->selection->getNeededChunks());
		$this->totalChunks = count($chunks);
		$this->chunksLeft = count($chunks);
		$fastSet = VectorUtils::product($this->selection->getSize()) < ConfigManager::getFastSetMax();
		$this->prepare($fastSet);
		$this->constructors = iterator_to_array($this->prepareConstructors($this->handler), false);
		foreach ($chunks as $chunk) {
			$handler->request($chunk);
		}
		while (ThreadData::canExecute() && EditThread::getInstance()->allowsExecution()) {
			if (($key = $handler->getNextChunk()) !== null) {
				$this->chunksLeft--;
				$this->run($key, $handler->getData());
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
		$this->finalize();
	}

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
		parent::putData($stream);
		$stream->putString($this->selection->fastSerialize());
		$stream->putString($this->context->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->selection = Selection::fastDeserialize($stream->getString());
		$this->context = SelectionContext::fastDeserialize($stream->getString());
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
}