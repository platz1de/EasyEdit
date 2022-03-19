<?php

namespace platz1de\EasyEdit\task\editing\pathfinding;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class PathfindingTask extends EditTask
{
	use SettingNotifier;

	private Vector3 $start;
	private Vector3 $end;
	private bool $allowDiagonal;
	private StaticBlock $block;

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Vector3               $start
	 * @param Vector3               $end
	 * @param bool                  $allowDiagonal
	 * @param StaticBlock           $block
	 * @return PathfindingTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Vector3 $start, Vector3 $end, bool $allowDiagonal, StaticBlock $block): PathfindingTask
	{
		$instance = new self($owner);
		EditTask::initEditTask($instance, $world, $data);
		$instance->start = $start;
		$instance->end = $end;
		$instance->allowDiagonal = $allowDiagonal;
		$instance->block = $block;
		return $instance;
	}

	/**
	 * @param string      $player
	 * @param string      $world
	 * @param Vector3     $start
	 * @param Vector3     $end
	 * @param bool        $allowDiagonal
	 * @param StaticBlock $block
	 */
	public static function queue(string $player, string $world, Vector3 $start, Vector3 $end, bool $allowDiagonal, StaticBlock $block): void
	{
		TaskInputData::fromTask(self::from($player, $world, new AdditionalDataManager(true, true), $start, $end, $allowDiagonal, $block));
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

	/**
	 * @param EditTaskHandler $handler
	 */
	public function executeEdit(EditTaskHandler $handler): void
	{
		/** @phpstan-var NodeHeap<Node> $open */
		$open = new NodeHeap();
		/** @var Node[] $collection */
		$collection = [];
		$closed = [];
		$checked = 0;
		$loadedChunks = []; //TODO: unload chunks after a set amount
		$max = ConfigManager::getPathfindingMax();

		$endX = $this->end->getFloorX();
		$endY = $this->end->getFloorY();
		$endZ = $this->end->getFloorZ();

		$open->insert(new Node($this->start->getFloorX(), $this->start->getFloorY(), $this->start->getFloorZ(), null, $endX, $endY, $endZ));
		while ($checked++ < $max) {
			/** @var Node $current */
			$current = $open->extract();
			unset($collection[$current->hash]);
			$closed[$current->hash] = $current->parentHash;
			$chunk = World::chunkHash($current->x >> 4, $current->z >> 4);
			if (!isset($loadedChunks[$chunk])) {
				$loadedChunks[$chunk] = true;
				if (!$this->requestRuntimeChunks($handler, [$chunk])) {
					return;
				}
			}
			if ($current->equals($endX, $endY, $endZ)) {
				$hash = $current->hash;
				while (isset($closed[$hash])) {
					World::getBlockXYZ($hash, $x, $y, $z);
					$handler->changeBlock($x, $y, $z, $this->block->get());
					$hash = $closed[$hash];
				}
				return;
			}
			if ($handler->getBlock($current->x, $current->y, $current->z) === 0) {
				foreach ($current->getSides($this->allowDiagonal) as $side) {
					$hash = World::blockHash($side->x, $side->y, $side->z);
					if ($side->y < World::Y_MIN || $side->y >= World::Y_MAX || isset($closed[$hash])) {
						continue;
					}
					if (isset($collection[$hash])) {
						$collection[$hash]->checkG($current);
						continue;
					}
					$open->insert($collection[$hash] = new Node($side->x, $side->y, $side->z, $current, $endX, $endY, $endZ));
				}
			}
		}
		EditThread::getInstance()->debug("Gave up searching");
	}

	/**
	 * @return BinaryBlockListStream
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new BinaryBlockListStream($this->getOwner(), $this->getWorld());
	}

	public function getTaskName(): string
	{
		return "line";
	}

	public function getProgress(): float
	{
		//TODO
		return 0;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->start);
		$stream->putVector($this->end);
		$stream->putBool($this->allowDiagonal);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->start = $stream->getVector();
		$this->end = $stream->getVector();
		$this->allowDiagonal = $stream->getBool();
		$this->block = StaticBlock::fromBlock(BlockFactory::getInstance()->fromFullBlock($stream->getInt()));
	}
}