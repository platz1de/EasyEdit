<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\world\World;

abstract class ExpandingTask extends EditTask
{
	private float $progress = 0; //worst case scenario
	protected ManagedChunkHandler $loader;

	/**
	 * @param string      $world
	 * @param BlockVector $start
	 */
	public function __construct(string $world, protected BlockVector $start)
	{
		parent::__construct($world);
	}

	public function executeInternal(): EditTaskResult
	{
		$this->prepare(true);

		$this->handler->setManager($manager = new ReferencedChunkManager($this->world));
		$this->loader = new ManagedChunkHandler($this->handler);
		ChunkRequestManager::setHandler($this->loader);
		$this->loader->request(World::chunkHash($this->start->x >> 4, $this->start->z >> 4));

		$this->runEdit(-1, $manager->getChunks());

		return $this->toTaskResult();
	}

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
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
		parent::putData($stream);
		$stream->putBlockVector($this->start);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->start = $stream->getBlockVector();
	}
}