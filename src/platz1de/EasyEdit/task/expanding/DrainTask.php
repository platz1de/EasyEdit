<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ConfigManager;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class DrainTask extends ExpandingTask
{
	use SettingNotifier;

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		$queue = new SplPriorityQueue();
		$scheduled = [];
		$startX = $this->start->getFloorX();
		$startY = $this->start->getFloorY();
		$startZ = $this->start->getFloorZ();
		$this->loader->registerRequestedChunks(World::chunkHash($startX >> 4, $startZ >> 4));
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
			if (!$this->loader->checkRuntimeChunk($chunk)) {
				return;
			}
			$res = $handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS;
			if ($res !== BlockTypeIds::WATER && $res !== BlockTypeIds::LAVA) {
				$this->loader->checkUnload($handler, $chunk);
				continue;
			}
			$handler->changeBlock($x, $y, $z, $air);
			foreach (Facing::ALL as $facing) {
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if (!isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
					$scheduled[$hash] = true;
					$this->loader->registerRequestedChunks(World::chunkHash($side->getFloorX() >> 4, $side->getFloorZ() >> 4));
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			$this->loader->checkUnload($handler, $chunk);
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}
}