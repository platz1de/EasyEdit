<?php

namespace platz1de\EasyEdit\task\editing\pathfinding;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\expanding\ExpandingTask;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
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
		$instance = new self($owner, $world, $data, $start);
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

	/**
	 * @param EditTaskHandler $handler
	 */
	public function executeEdit(EditTaskHandler $handler): void
	{
		$open = new NodeHeap();
		/** @var Node[] $collection */
		$collection = [];
		$closed = [];
		$checked = 0;
		//TODO: unload chunks after a set amount
		$max = ConfigManager::getPathfindingMax();

		$endX = $this->end->getFloorX();
		$endY = $this->end->getFloorY();
		$endZ = $this->end->getFloorZ();

		if (!$this->checkRuntimeChunk($handler, World::chunkHash($this->getPosition()->getFloorX(), $this->getPosition()->getFloorZ()), 0, 1)) {
			return;
		}

		$open->insert(new Node($this->getPosition()->getFloorX(), $this->getPosition()->getFloorY(), $this->getPosition()->getFloorZ(), null, $endX, $endY, $endZ));
		while ($checked++ < $max) {
			/** @var Node $current */
			$current = $open->extract();
			unset($collection[$current->hash]);
			$closed[$current->hash] = $current->parentHash;
			$chunk = World::chunkHash($current->x >> 4, $current->z >> 4);
			$this->checkRuntimeChunk($handler, $chunk, $checked, $max);
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
		return new BinaryBlockListStream($this->getOwner(), $this->getWorld());
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