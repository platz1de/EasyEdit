<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\World;

class LineTask extends EditTask
{
	use SettingNotifier;

	private Vector3 $start;
	private Vector3 $end;
	private StaticBlock $block;

	/**
	 * @var Vector3[]
	 * These are 16 blocks in the worst case
	 */
	private array $blocks = [];

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Vector3               $start
	 * @param Vector3               $end
	 * @param StaticBlock           $block
	 * @return LineTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Vector3 $start, Vector3 $end, StaticBlock $block): LineTask
	{
		$instance = new self($owner);
		EditTask::initEditTask($instance, $world, $data);
		$instance->start = $start;
		$instance->end = $end;
		$instance->block = $block;
		return $instance;
	}

	/**
	 * @param string      $player
	 * @param string      $world
	 * @param Vector3     $start
	 * @param Vector3     $end
	 * @param StaticBlock $block
	 */
	public static function queue(string $player, string $world, Vector3 $start, Vector3 $end, StaticBlock $block): void
	{
		TaskInputData::fromTask(self::from($player, $world, new AdditionalDataManager(true, true), $start, $end, $block));
	}

	public function execute(): void
	{
		ChunkCollector::init($this->getWorld());
		$current = null;
		//offset points to not yield blocks beyond the endings
		foreach (VoxelRayTrace::betweenPoints($this->start->add(0.5, 0.5, 0.5), $this->end->add(0.5, 0.5, 0.5)) as $pos) {
			if ($current === null) {
				$current = World::chunkHash($pos->x >> Block::INTERNAL_METADATA_BITS, $pos->z >> Block::INTERNAL_METADATA_BITS);
			} elseif ($current !== ($c = World::chunkHash($pos->x >> Block::INTERNAL_METADATA_BITS, $pos->z >> Block::INTERNAL_METADATA_BITS))) {
				$this->requestChunks([$current]);
				$this->blocks = [];
				$current = $c;
			}
			$this->blocks[] = $pos;
		}
		if($current !== null) {
            $this->requestChunks([$current]);
        }
		ChunkCollector::clear();
	}

	/**
	 * @param EditTaskHandler $handler
	 */
	public function executeEdit(EditTaskHandler $handler): void
	{
		foreach ($this->blocks as $pos) {
			$handler->changeBlock((int) $pos->x, (int) $pos->y, (int) $pos->z, $this->block->get());
		}
	}

	/**
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		//TODO: optimize
		return new StaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->start, $this->end);
	}

	public function getTaskName(): string
	{
		return "line";
	}

	public function getProgress(): float
	{
		$current = $this->blocks[0] ?? $this->start;
		return $current->distance($this->start) / $this->start->distance($this->end);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->start);
		$stream->putVector($this->end);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->start = $stream->getVector();
		$this->end = $stream->getVector();
		$this->block = StaticBlock::fromBlock(BlockFactory::getInstance()->fromFullBlock($stream->getInt()));
	}
}