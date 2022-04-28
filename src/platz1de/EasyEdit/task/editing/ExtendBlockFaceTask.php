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
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class ExtendBlockFaceTask extends EditTask
{
	use SettingNotifier;

	private int $face;

	private float $progress = 0; //worst case scenario

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Vector3               $block
	 * @param int                   $face
	 * @return ExtendBlockFaceTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Vector3 $block, int $face): ExtendBlockFaceTask
	{
		$instance = new self($owner, $world, $data, $block);
		$instance->face = $face;
		return $instance;
	}

	/**
	 * @param string  $player
	 * @param string  $world
	 * @param Vector3 $block
	 * @param int     $face
	 */
	public static function queue(string $player, string $world, Vector3 $block, int $face): void
	{
		TaskInputData::fromTask(self::from($player, $world, new AdditionalDataManager(true, true), $block, $face));
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
		$startChunk = World::chunkHash($this->getPosition()->getFloorX() >> 4, $this->getPosition()->getFloorZ() >> 4);
		if (!$this->requestRuntimeChunks($handler, [$startChunk])) {
			return;
		}
		$target = $handler->getBlock($this->getPosition()->getFloorX(), $this->getPosition()->getFloorY(), $this->getPosition()->getFloorZ());
		$offset = $this->getPosition()->subtractVector($start = $this->getPosition()->getSide($this->face));
		$ignore = HeightMapCache::getIgnore();
		if (($k = array_search($target, $ignore)) !== false) {
			unset($ignore[$k]);
		}

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$loadedChunks = [$startChunk];
		$offsetX = $offset->getFloorX();
		$offsetY = $offset->getFloorY();
		$offsetZ = $offset->getFloorZ();
		$max = ConfigManager::getFillDistance();
		$requestedChunks = [World::chunkHash($start->getFloorX() >> 4, $start->getFloorZ() >> 4) => 1];
		if (isset($requestedChunks[$off = World::chunkHash(($start->getFloorX() + $offsetX) >> 4, ($start->getFloorZ() + $offsetZ) >> 4)])) {
			$requestedChunks[$off]++;
		} else {
			$requestedChunks[$off] = 1;
		}

		$queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
		$queue->insert(World::blockHash($start->getFloorX(), $start->getFloorY(), $start->getFloorZ()), 0);
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
			$c = World::chunkHash(($x + $offsetX) >> 4, ($z + $offsetZ) >> 4);
			if (!isset($loadedChunks[$c])) {
				$loadedChunks[$c] = true;
				if (!$this->requestRuntimeChunks($handler, [$c])) {
					return;
				}
			}
			$requestedChunks[$chunk]--;
			$requestedChunks[$c]--;
			if ($handler->getBlock($x + $offsetX, $y + $offsetY, $z + $offsetZ) !== $target || !in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
				if ($requestedChunks[$chunk] <= 0) {
					unset($requestedChunks[$chunk], $loadedChunks[$chunk]);
					$this->sendRuntimeChunks($handler, [$chunk]);
				}
				if (isset($requestedChunks[$c]) && $requestedChunks[$c] <= 0) {
					unset($requestedChunks[$c], $loadedChunks[$c]);
					$this->sendRuntimeChunks($handler, [$c]);
				}
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
					if (!isset($requestedChunks[$h = World::chunkHash($side->getFloorX() >> 4, $side->getFloorZ() >> 4)])) {
						$requestedChunks[$h] = 0;
					}
					if (!isset($requestedChunks[$ho = World::chunkHash(($side->getFloorX() + $offsetX) >> 4, ($side->getFloorZ() + $offsetZ) >> 4)])) {
						$requestedChunks[$h0] = 0;
					}
					$requestedChunks[$h]++;
					$requestedChunks[$ho]++;
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			if ($requestedChunks[$chunk] <= 0) {
				unset($requestedChunks[$chunk], $loadedChunks[$chunk]);
				$this->sendRuntimeChunks($handler, [$chunk]);
			}
			if (isset($requestedChunks[$c]) && $requestedChunks[$c] <= 0) {
				unset($requestedChunks[$c], $loadedChunks[$c]);
				$this->sendRuntimeChunks($handler, [$c]);
			}
		}
	}

	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->getPosition());
	}

	public function getTaskName(): string
	{
		return "expand";
	}

	public function getProgress(): float
	{
		return $this->progress; //Unknown
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