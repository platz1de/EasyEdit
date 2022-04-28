<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ConfigManager;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class DrainTask extends EditTask
{
	use SettingNotifier;

	private float $progress = 0; //worst case scenario

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

	public function execute(): void
	{
		$this->getDataManager()->useFastSet();
		$this->getDataManager()->setFinal();
		ChunkCollector::init($this->getWorld());
		ChunkCollector::collectInput(ChunkInputData::empty());
		$this->run();
		ChunkCollector::clear();
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$target = [BlockLegacyIds::FLOWING_WATER, BlockLegacyIds::STILL_WATER, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::STILL_LAVA];

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$loadedChunks = [];
		$startX = $this->getPosition()->getFloorX();
		$startY = $this->getPosition()->getFloorY();
		$startZ = $this->getPosition()->getFloorZ();
		$requestedChunks = [World::chunkHash($startX >> 4, $startZ >> 4) => 1];
		$max = ConfigManager::getFillDistance();

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
			if (!isset($loadedChunks[$chunk])) {
				$loadedChunks[$chunk] = true;
				$this->progress = -$current["priority"] / $max;
				if (!$this->requestRuntimeChunks($handler, [$chunk])) {
					return;
				}
			}
			$requestedChunks[$chunk]--;
			if (!in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $target, true)) {
				if ($requestedChunks[$chunk] <= 0) {
					unset($requestedChunks[$chunk], $loadedChunks[$chunk]);
					$this->sendRuntimeChunks($handler, [$chunk]);
				}
				continue;
			}
			$handler->changeBlock($x, $y, $z, 0);
			foreach (Facing::ALL as $facing) {
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if (!isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
					$scheduled[$hash] = true;
					if (!isset($requestedChunks[$h = World::chunkHash($side->getFloorX() >> 4, $side->getFloorZ() >> 4)])) {
						$requestedChunks[$h] = 0;
					}
					$requestedChunks[$h]++;
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			if ($requestedChunks[$chunk] <= 0) {
				unset($requestedChunks[$chunk], $loadedChunks[$chunk]);
				$this->sendRuntimeChunks($handler, [$chunk]);
			}
		}
	}

	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->getPosition());
	}

	public function getTaskName(): string
	{
		return "fill";
	}

	public function getProgress(): float
	{
		return $this->progress; //Unknown
	}
}