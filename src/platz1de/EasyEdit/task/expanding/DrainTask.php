<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\utils\ConfigManager;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\world\World;
use SplPriorityQueue;

class DrainTask extends ExpandingTask
{
	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @throws CancelException
	 */
	public function executeEdit(EditTaskHandler $handler, ManagedChunkHandler $loader): void
	{
		$queue = new SplPriorityQueue();
		$scheduled = [];
		$startX = $this->start->x;
		$startY = $this->start->y;
		$startZ = $this->start->z;
		$loader->registerRequestedChunks(World::chunkHash($startX >> 4, $startZ >> 4));
		$limit = ConfigManager::getFillDistance();
		$air = VanillaBlocks::AIR()->getStateId();

		$queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
		$queue->insert(World::blockHash($startX, $startY, $startZ), 0);
		while (!$queue->isEmpty()) {
			/** @var array{data: int, priority: int} $current */
			$current = $queue->extract();
			if (-$current["priority"] > $limit) {
				break;
			}
			World::getBlockXYZ($current["data"], $x, $y, $z);
			$chunk = World::chunkHash($x >> 4, $z >> 4);
			$this->updateProgress(-$current["priority"], $limit);
			$loader->checkRuntimeChunk($chunk);
			$res = $handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS;
			if ($res !== BlockTypeIds::WATER && $res !== BlockTypeIds::LAVA) {
				$loader->checkUnload($handler, $chunk);
				continue;
			}
			$handler->changeBlock($x, $y, $z, $air);
			foreach (Facing::ALL as $facing) {
				$side = (new BlockVector($x, $y, $z))->getSide($facing);
				if (!isset($scheduled[$hash = $side->getBlockHash()])) {
					$scheduled[$hash] = true;
					$loader->registerRequestedChunks($side->getChunkHash());
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			$loader->checkUnload($handler, $chunk);
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}
}