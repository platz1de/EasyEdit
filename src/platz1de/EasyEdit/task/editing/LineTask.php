<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\World;

class LineTask extends EditTask
{
	use SettingNotifier;

	/**
	 * @var BlockVector[]
	 * These are 48 blocks in the worst case
	 */
	private array $blocks = [];

	/**
	 * @param string      $world
	 * @param BlockVector $start
	 * @param BlockVector $end
	 * @param StaticBlock $block
	 */
	public function __construct(string $world, private BlockVector $start, private BlockVector $end, protected StaticBlock $block)
	{
		parent::__construct($world);
	}

	public function execute(): void
	{
		$chunkHandler = new SingleChunkHandler($this->world);
		ChunkRequestManager::setHandler($chunkHandler);
		$current = null;
		$this->prepare(true);
		//offset points to not yield blocks beyond the endings
		foreach (VoxelRayTrace::betweenPoints($this->start->toVector()->add(0.5, 0.5, 0.5), $this->end->toVector()->add(0.5, 0.5, 0.5)) as $pos) {
			if ($current === null) {
				$current = World::chunkHash($pos->x >> Block::INTERNAL_STATE_DATA_BITS, $pos->z >> Block::INTERNAL_STATE_DATA_BITS);
			} elseif ($current !== ($c = World::chunkHash($pos->x >> Block::INTERNAL_STATE_DATA_BITS, $pos->z >> Block::INTERNAL_STATE_DATA_BITS))) {
				if (!$this->executePart($chunkHandler, $current)) {
					return;
				}
				$this->blocks = [];
				$current = $c;
			}
			$this->blocks[] = BlockVector::fromVector($pos);
		}
		if ($current !== null && !$this->executePart($chunkHandler, $current)) {
			return;
		}
		$this->finalize();
	}

	private function executePart(SingleChunkHandler $handler, int $chunk): bool
	{
		$handler->request($chunk);
		while ($handler->getNextChunk() === null && ThreadData::canExecute() && EditThread::getInstance()->allowsExecution()) {
			EditThread::getInstance()->waitForData();
		}
		if ($handler->getNextChunk() === null) {
			return false;
		}
		$this->run($chunk, $handler->getData());
		return true;
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		foreach ($this->blocks as $pos) {
			$handler->changeBlock($pos->x, $pos->y, $pos->z, $this->block->get());
		}
	}

	/**
	 * @return BinaryBlockListStream
	 */
	public function createUndoBlockList(): BlockListSelection
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
		return $current->diff($this->end)->length() / $this->start->diff($this->end)->length();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putBlockVector($this->start);
		$stream->putBlockVector($this->end);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->start = $stream->getBlockVector();
		$this->end = $stream->getBlockVector();
		$this->block = new StaticBlock($stream->getInt());
	}
}