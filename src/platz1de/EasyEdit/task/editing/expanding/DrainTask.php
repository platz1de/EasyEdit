<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
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
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Vector3               $start
	 * @return DrainTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Vector3 $start): DrainTask
	{
		return new self($owner, $world, $data, $start);
	}

	/**
	 * @param string  $player
	 * @param string  $world
	 * @param Vector3 $start
	 */
	public static function queue(string $player, string $world, Vector3 $start): void
	{
		TaskInputData::fromTask(self::from($player, $world, new AdditionalDataManager(true, true), $start));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$target = [BlockLegacyIds::FLOWING_WATER, BlockLegacyIds::STILL_WATER, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::STILL_LAVA];

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$startX = $this->getPosition()->getFloorX();
		$startY = $this->getPosition()->getFloorY();
		$startZ = $this->getPosition()->getFloorZ();
		$this->registerRequestedChunks(World::chunkHash($startX >> 4, $startZ >> 4));
		$max = ConfigManager::getFillDistance();

		if (!$this->checkRuntimeChunk($handler, World::chunkHash($startX, $startZ), 0, 1)) {
			return;
		}

		$queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
		$queue->insert(World::blockHash($startX, $startY, $startZ), 0);
		while (!$queue->isEmpty()) {
			/** @var array{data: int, priority: int} $current */
			$current = $queue->extract();
			if (-$current["priority"] > $max) {
				break;
			}
			World::getBlockXYZ($current["data"], $x, $y, $z);
			$chunk = World::chunkHash($x >> 4, $z >> 4);
			if (!$this->checkRuntimeChunk($handler, $chunk, -$current["priority"], $max)) {
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