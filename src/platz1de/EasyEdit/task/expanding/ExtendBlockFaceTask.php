<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class ExtendBlockFaceTask extends ExpandingTask
{
	/**
	 * @param string      $world
	 * @param BlockVector $block
	 * @param int         $face
	 */
	public function __construct(string $world, BlockVector $block, private int $face)
	{
		parent::__construct($world, $block);
	}

	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @throws CancelException
	 */
	public function executeEdit(EditTaskHandler $handler, ManagedChunkHandler $loader): void
	{
		$target = $handler->getBlock($this->start->x, $this->start->y, $this->start->z);
		$offset = $this->start->diff($start = $this->start->getSide($this->face));
		$ignore = HeightMapCache::getIgnore();
		if (($k = array_search($target, $ignore, true)) !== false) {
			unset($ignore[$k]);
		}

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$offsetX = $offset->x;
		$offsetY = $offset->y;
		$offsetZ = $offset->z;
		$limit = ConfigManager::getFillDistance();
		$loader->registerRequestedChunks(World::chunkHash($start->x >> 4, $start->z >> 4));
		$loader->registerRequestedChunks(World::chunkHash(($start->x + $offsetX) >> 4, ($start->z + $offsetZ) >> 4));

		$queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
		$queue->insert(World::blockHash($start->x, $start->y, $start->z), 0);
		while (!$queue->isEmpty()) {
			/** @var array{data: int, priority: int} $current */
			$current = $queue->extract();
			if (-$current["priority"] > $limit) {
				break;
			}
			World::getBlockXYZ($current["data"], $x, $y, $z);
			$chunk = World::chunkHash($x >> 4, $z >> 4);
			$c = World::chunkHash(($x + $offsetX) >> 4, ($z + $offsetZ) >> 4);
			$this->updateProgress(-$current["priority"], $limit);
			$loader->checkRuntimeChunk($chunk);
			$loader->checkRuntimeChunk($c);
			if ($handler->getBlock($x + $offsetX, $y + $offsetY, $z + $offsetZ) !== $target || !in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
				$loader->checkUnload($handler, $chunk);
				$loader->checkUnload($handler, $c);
				continue;
			}
			$handler->changeBlock($x, $y, $z, $target);
			foreach (Facing::ALL as $facing) {
				if (Facing::axis($facing) === Facing::axis($this->face)) {
					continue;
				}
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if (!isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
					$scheduled[$hash] = true;
					$loader->registerRequestedChunks(World::chunkHash($side->getFloorX() >> 4, $side->getFloorZ() >> 4));
					$loader->registerRequestedChunks(World::chunkHash(($side->getFloorX() + $offsetX) >> 4, ($side->getFloorZ() + $offsetZ) >> 4));
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			$loader->checkUnload($handler, $chunk);
			$loader->checkUnload($handler, $c);
		}
	}

	public function getTaskName(): string
	{
		return "expand";
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putByte($this->face);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->face = $stream->getByte();
	}
}