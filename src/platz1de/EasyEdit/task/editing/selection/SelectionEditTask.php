<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\SingleChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

abstract class SelectionEditTask extends EditTask
{
	protected Selection $selection;
	protected SelectionContext $context;
	private int $totalPieces;
	private int $piecesLeft;

	/**
	 * @param Selection             $selection
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, ?SelectionContext $context = null)
	{
		$this->selection = $selection;
		$this->context = $context ?? SelectionContext::full();
		parent::__construct($selection->getWorldName());
	}

	public function execute(): void
	{
		$handler = $this->getChunkHandler();
		ChunkRequestManager::setHandler($handler);
		StorageModule::checkFinished();
		$chunks = $this->orderChunks($this->selection->getNeededChunks());
		$this->totalPieces = count($chunks);
		$this->piecesLeft = count($chunks);
		$fastSet = VectorUtils::product($this->selection->getSize()) < ConfigManager::getFastSetMax();
		foreach ($chunks as $chunk) {
			$handler->request($chunk);
		}
		while (ThreadData::canExecute() && EditThread::getInstance()->allowsExecution()) {
			if (($key = $handler->getKey()) !== null) {
				World::getXZ($key, $x, $z);
				$min = new Vector3($x << 4, World::Y_MIN, $z << 4);
				$max = new Vector3(($x << 4) + 15, World::Y_MAX, ($z << 4) + 15);
				$this->piecesLeft--;
				$this->run($fastSet, $min, $max, $key, $handler->getNext());
			}
			if ($this->piecesLeft <= 0) {
				break;
			}
			if ($handler->getKey() === null) {
				EditThread::getInstance()->waitForData();
			} else {
				EditThread::getInstance()->parseInput();
			}
		}
		$this->finalize();
	}

	/**
	 * @param int[] $chunks
	 * @return int[]
	 */
	protected function orderChunks(array $chunks): array
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
	 * @return SingleChunkHandler
	 */
	public function getChunkHandler(): SingleChunkHandler
	{
		return new SingleChunkHandler($this->getWorld());
	}

	/**
	 * @return int
	 */
	public function getTotalPieces(): int
	{
		return $this->totalPieces;
	}

	/**
	 * @return int
	 */
	public function getPiecesLeft(): int
	{
		return $this->piecesLeft;
	}

	public function getProgress(): float
	{
		return ($this->totalPieces - $this->piecesLeft) / $this->totalPieces;
	}
}