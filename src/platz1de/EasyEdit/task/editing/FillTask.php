<?php

namespace platz1de\EasyEdit\task\editing;

use BadMethodCallException;
use platz1de\EasyEdit\pattern\block\StaticBlock;
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
use pocketmine\block\BlockFactory;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use SplPriorityQueue;

class FillTask extends EditTask
{
	use SettingNotifier;

	private Vector3 $start;
	private int $direction;
	private StaticBlock $block;

	private float $progress = 0; //worst case scenario

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
		$instance = new self($owner);
		EditTask::initEditTask($instance, $world, $data);
		$instance->start = $start;
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
		$ignore = HeightMapCache::getIgnore();
		unset($ignore[array_search($this->block->getId(), $ignore)]);

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$loadedChunks = [];
		$id = $this->block->get();
		$startX = $this->start->getFloorX();
		$startY = $this->start->getFloorY();
		$startZ = $this->start->getFloorZ();
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
			if (!in_array($handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
				continue;
			}
			$handler->changeBlock($x, $y, $z, $id);
			foreach (Facing::ALL as $facing) {
				$side = (new Vector3($x, $y, $z))->getSide($facing);
				if ($validate($side) && !isset($scheduled[$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())])) {
					$scheduled[$hash] = true;
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
		}
	}

	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->start);
	}

	public function getTaskName(): string
	{
		return "fill";
	}

	public function getProgress(): float
	{
		return $this->progress; //Unknown
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->start);
		$stream->putByte($this->direction);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->start = $stream->getVector();
		$this->direction = $stream->getByte();
		$this->block = StaticBlock::fromBlock(BlockFactory::getInstance()->fromFullBlock($stream->getInt()));
	}
}