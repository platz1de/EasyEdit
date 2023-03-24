<?php

namespace platz1de\EasyEdit\task\expanding;

use BadMethodCallException;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class FillTask extends ExpandingTask
{
	use SettingNotifier;

	/**
	 * @param string      $world
	 * @param BlockVector $start
	 * @param int         $direction
	 * @param StaticBlock $block
	 */
	public function __construct(string $world, BlockVector $start, private int $direction, private StaticBlock $block)
	{
		parent::__construct($world, $start);
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		$ignore = HeightMapCache::getIgnore();
		if (($k = array_search($this->block->getTypeId(), $ignore, true)) !== false) {
			unset($ignore[$k]);
		}

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$id = $this->block->get();
		$startX = $this->start->x;
		$startY = $this->start->y;
		$startZ = $this->start->z;
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
		$limit = ConfigManager::getFillDistance();

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
			if (!in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
				$this->loader->checkUnload($handler, $chunk);
				continue;
			}
			$handler->changeBlock($x, $y, $z, $id);
			foreach (Facing::ALL as $facing) {
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if ($validate($side) && !isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
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