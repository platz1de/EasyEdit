<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\Position;
use pocketmine\world\World;

class LineTask extends EditTask
{
	use SettingNotifier;

	private Vector3 $start;
	private Vector3 $end;
	private StaticBlock $block;

	/**
	 * @var Vector3[]
	 * These are 48 blocks in the worst case
	 */
	private array $blocks = [];

	/**
	 * @param Position    $start
	 * @param Vector3     $end
	 * @param StaticBlock $block
	 */
	public function __construct(Position $start, Vector3 $end, StaticBlock $block)
	{
		$this->start = $start->asVector3();
		$this->end = $end->asVector3();
		$this->block = $block;
		parent::__construct($start->getWorld()->getFolderName());
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
				$this->requestChunks([$current], true);
				$this->blocks = [];
				$current = $c;
			}
			$this->blocks[] = $pos;
		}
		if ($current !== null) {
			$this->requestChunks([$current], true);
		}
		$this->finalize();
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

	public function getProgress(): float
	{
		$current = $this->blocks[0] ?? $this->start;
		return $current->distance($this->end) / $this->start->distance($this->end);
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
		$this->block = new StaticBlock($stream->getInt());
	}
}