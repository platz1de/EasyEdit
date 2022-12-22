<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

abstract class ExpandingTask extends EditTask
{
	protected Vector3 $start;
	private float $progress = 0; //worst case scenario
	protected ManagedChunkHandler $loader;

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
		$this->prepare(true);

		$this->handler->setManager($manager = new ReferencedChunkManager($this->world));
		$this->loader = new ManagedChunkHandler($this->handler);
		ChunkRequestManager::setHandler($this->loader);
		if (!$this->loader->request(World::chunkHash($this->start->getFloorX() >> 4, $this->start->getFloorZ() >> 4))) {
			$this->finalize();
			return;
		}

		$this->run(-1, $manager->getChunks());

		$this->finalize();
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
		parent::putData($stream);
		$stream->putVector($this->start);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->start = $stream->getVector();
	}
}