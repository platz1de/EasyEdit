<?php

namespace platz1de\EasyEdit\task\pathfinding;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\task\expanding\ExpandingTask;
use platz1de\EasyEdit\task\expanding\ManagedChunkHandler;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class PathfindingTask extends ExpandingTask
{
	use SettingNotifier;

	private Vector3 $end;
	private bool $allowDiagonal;
	private StaticBlock $block;

	/**
	 * @param string      $world
	 * @param Vector3     $start
	 * @param Vector3     $end
	 * @param bool        $allowDiagonal
	 * @param StaticBlock $block
	 */
	public function __construct(string $world, Vector3 $start, Vector3 $end, bool $allowDiagonal, StaticBlock $block)
	{
		$this->end = $end;
		$this->allowDiagonal = $allowDiagonal;
		$this->block = $block;
		parent::__construct($world, $start);
	}

	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @return void
	 */
	protected function run(EditTaskHandler $handler, ManagedChunkHandler $loader): void
	{
		$open = new NodeHeap();
		/** @var Node[] $collection */
		$collection = [];
		$closed = [];
		$checked = 0;
		//TODO: unload chunks after a set amount
		$limit = ConfigManager::getPathfindingMax();

		$startX = $this->start->getFloorX();
		$startY = $this->start->getFloorY();
		$startZ = $this->start->getFloorZ();
		$endX = $this->end->getFloorX();
		$endY = $this->end->getFloorY();
		$endZ = $this->end->getFloorZ();

		$open->insert(new Node($startX, $startY, $startZ, null, $endX, $endY, $endZ));
		while ($checked++ < $limit) {
			/** @var Node $current */
			$current = $open->extract();
			unset($collection[$current->hash]);
			$closed[$current->hash] = $current->parentHash;
			$chunk = World::chunkHash($current->x >> 4, $current->z >> 4);
			$this->updateProgress($checked, $limit);
			if (!$loader->checkRuntimeChunk($chunk)) {
				return;
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
					$hash = World::blockHash($side->getFloorX(), $side->getFloorY(), $side->getFloorZ());
					if ($side->y < World::Y_MIN || $side->y >= World::Y_MAX || isset($closed[$hash])) {
						continue;
					}
					if (isset($collection[$hash])) {
						$collection[$hash]->checkG($current);
						continue;
					}
					$open->insert($collection[$hash] = new Node($side->getFloorX(), $side->getFloorY(), $side->getFloorZ(), $current, $endX, $endY, $endZ));
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
		return new BinaryBlockListStream($this->getWorld());
	}

	public function getTaskName(): string
	{
		return "line";
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->end);
		$stream->putBool($this->allowDiagonal);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->end = $stream->getVector();
		$this->allowDiagonal = $stream->getBool();
		$this->block = new StaticBlock($stream->getInt());
	}
}