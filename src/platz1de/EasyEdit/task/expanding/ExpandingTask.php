<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\output\session\HistoryCacheData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

abstract class ExpandingTask extends ExecutableTask
{
	protected string $world;
	protected Vector3 $start;
	private float $progress = 0; //worst case scenario
	protected int $totalBlocks = 0;
	protected float $totalTime = 0;

	/**
	 * @param string  $world
	 * @param Vector3 $start
	 */
	public function __construct(string $world, Vector3 $start)
	{
		$this->start = $start;
		$this->world = $world;
		parent::__construct();
	}

	public function execute(): void
	{
		$start = microtime(true);

		$undo = $this->getUndoBlockList();
		StorageModule::startCollecting($undo);
		$handler = new EditTaskHandler($undo, true);
		$handler->setManager(new ReferencedChunkManager($this->world));
		$loader = new ManagedChunkHandler($handler);
		ChunkRequestManager::setHandler($loader);
		if (!$loader->request(World::chunkHash($this->start->getFloorX() >> 4, $this->start->getFloorZ() >> 4))) {
			$this->finalize($handler);
			return;
		}

		$this->run($handler, $loader);

		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $handler->getChangedBlockCount() . " blocks (" . $handler->getReadBlockCount() . " read, " . $handler->getWrittenBlockCount() . " written)");

		$this->totalTime += microtime(true) - $start;
		$this->totalBlocks += $handler->getChangedBlockCount();

		$this->finalize($handler);
	}

	abstract protected function run(EditTaskHandler $handler, ManagedChunkHandler $loader): void;

	public function finalize(EditTaskHandler $handler): void
	{
		if (!$this->useDefaultHandler()) {
			return;
		}
		$this->sendOutputPacket(new ResultingChunkData($this->world, $handler->getResult()->getChunks(), $handler->prepareAllInjectionData()));
		$changeId = StorageModule::finishCollecting();
		$this->sendOutputPacket(new HistoryCacheData($changeId, false));
		$this->notifyUser((string) round($this->totalTime, 2), MixedUtils::humanReadable($this->totalBlocks));
		ChunkRequestManager::clear();
	}

	/**
	 * @param string $time
	 * @param string $changed
	 */
	abstract public function notifyUser(string $time, string $changed): void;

	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getWorld(), $this->start);
	}

	public function updateProgress(int $current, int $max): void
	{
		$this->progress = $current / $max;
	}

	public function getProgress(): float
	{
		return $this->progress; //Unknown
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);
		$stream->putVector($this->start);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();
		$this->start = $stream->getVector();
	}

	/**
	 * @return string
	 */
	public function getWorld(): string
	{
		return $this->world;
	}
}