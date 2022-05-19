<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use BadMethodCallException;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class FillTask extends ExpandingTask
{
	use SettingNotifier;

	private int $direction;
	private StaticBlock $block;

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Vector3               $start
	 * @param int                   $direction
	 * @param StaticBlock           $block
	 * @return FillTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Vector3 $start, int $direction, StaticBlock $block): FillTask
	{
		$instance = new self($owner, $world, $data, $start);
		$instance->direction = $direction;
		$instance->block = $block;
		return $instance;
	}

	/**
	 * @param string      $player
	 * @param string      $world
	 * @param Vector3     $start
	 * @param int         $direction
	 * @param StaticBlock $block
	 */
	public static function queue(string $player, string $world, Vector3 $start, int $direction, StaticBlock $block): void
	{
		TaskInputData::fromTask(self::from($player, $world, new AdditionalDataManager(true, true), $start, $direction, $block));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$ignore = HeightMapCache::getIgnore();
		if (($k = array_search($this->block->getId(), $ignore, true)) !== false) {
			unset($ignore[$k]);
		}

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$id = $this->block->get();
		$startX = $this->getPosition()->getFloorX();
		$startY = $this->getPosition()->getFloorY();
		$startZ = $this->getPosition()->getFloorZ();
		$this->registerRequestedChunks(World::chunkHash($startX >> 4, $startZ >> 4));
		$validate = match ($this->direction) {
			Facing::DOWN => static function (Vector3 $pos) use ($startY) {
				return $pos->getFloorY() <= $startY;
			},
			Facing::UP => static function (Vector3 $pos) use ($startY) {
				return $pos->getFloorY() >= $startY;
			},
			Facing::NORTH => static function (Vector3 $pos) use ($startZ) {
				return $pos->getFloorZ() <= $startZ;
			},
			Facing::SOUTH => static function (Vector3 $pos) use ($startZ) {
				return $pos->getFloorZ() >= $startZ;
			},
			Facing::WEST => static function (Vector3 $pos) use ($startX) {
				return $pos->getFloorX() <= $startX;
			},
			Facing::EAST => static function (Vector3 $pos) use ($startX) {
				return $pos->getFloorX() >= $startX;
			},
			default => throw new BadMethodCallException("Invalid direction")
		};
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
			if (!in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
				$this->checkUnload($handler, $chunk);
				continue;
			}
			$handler->changeBlock($x, $y, $z, $id);
			foreach (Facing::ALL as $facing) {
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if ($validate($side) && !isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
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

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putByte($this->direction);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getByte();
		$this->block = new StaticBlock($stream->getInt());
	}
}