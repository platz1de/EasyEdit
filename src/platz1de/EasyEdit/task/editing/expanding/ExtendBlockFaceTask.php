<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
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

class ExtendBlockFaceTask extends ExpandingTask
{
	use SettingNotifier;

	private int $face;

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

	public function executeEdit(EditTaskHandler $handler): void
	{
		$startChunk = World::chunkHash($this->getPosition()->getFloorX() >> 4, $this->getPosition()->getFloorZ() >> 4);
		if (!$this->checkRuntimeChunk($handler, $startChunk, 0, 1)) {
			return;
		}
		$target = $handler->getBlock($this->getPosition()->getFloorX(), $this->getPosition()->getFloorY(), $this->getPosition()->getFloorZ());
		$offset = $this->getPosition()->subtractVector($start = $this->getPosition()->getSide($this->face));
		$ignore = HeightMapCache::getIgnore();
		if (($k = array_search($target, $ignore, true)) !== false) {
			unset($ignore[$k]);
		}

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$offsetX = $offset->getFloorX();
		$offsetY = $offset->getFloorY();
		$offsetZ = $offset->getFloorZ();
		$max = ConfigManager::getFillDistance();
		$this->registerRequestedChunks(World::chunkHash($start->getFloorX() >> 4, $start->getFloorZ() >> 4));
		$this->registerRequestedChunks(World::chunkHash(($start->getFloorX() + $offsetX) >> 4, ($start->getFloorZ() + $offsetZ) >> 4));

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
			$c = World::chunkHash(($x + $offsetX) >> 4, ($z + $offsetZ) >> 4);
			$this->checkRuntimeChunk($handler, $chunk, -$current["priority"], $max);
			$this->checkRuntimeChunk($handler, $c, -$current["priority"], $max);
			if ($handler->getBlock($x + $offsetX, $y + $offsetY, $z + $offsetZ) !== $target || !in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
				$this->checkUnload($handler, $chunk);
				$this->checkUnload($handler, $c);
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
					$this->registerRequestedChunks(World::chunkHash($side->getFloorX() >> 4, $side->getFloorZ() >> 4));
					$this->registerRequestedChunks(World::chunkHash(($side->getFloorX() + $offsetX) >> 4, ($side->getFloorZ() + $offsetZ) >> 4));
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			$this->checkUnload($handler, $chunk);
			$this->checkUnload($handler, $c);
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