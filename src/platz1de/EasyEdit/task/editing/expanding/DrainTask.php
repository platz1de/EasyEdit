<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ConfigManager;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class DrainTask extends ExpandingTask
{
	use SettingNotifier;

	/**
	 * @param EditTaskHandler $handler
	 * @param Vector3         $min
	 * @param Vector3         $max
	 * @return void
	 */
	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$target = [BlockLegacyIds::FLOWING_WATER, BlockLegacyIds::STILL_WATER, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::STILL_LAVA];

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$startX = $this->start->getFloorX();
		$startY = $this->start->getFloorY();
		$startZ = $this->start->getFloorZ();
		$this->registerRequestedChunks(World::chunkHash($startX >> 4, $startZ >> 4));
		$limit = ConfigManager::getFillDistance();

		if (!$this->checkRuntimeChunk($handler, World::chunkHash($startX, $startZ), 0, 1)) {
			return;
		}

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
			if (!$this->checkRuntimeChunk($handler, $chunk, -$current["priority"], $limit)) {
				return;
			}
			if (!in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $target, true)) {
				$this->checkUnload($handler, $chunk);
				continue;
			}
			$handler->changeBlock($x, $y, $z, 0);
			foreach (Facing::ALL as $facing) {
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if (!isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
					$scheduled[$hash] = true;
					$this->registerRequestedChunks(World::chunkHash($side->getFloorX() >> 4, $side->getFloorZ() >> 4));
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			$this->checkUnload($handler, $chunk);
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}
}