<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\World;

class LineTask extends EditTask
{
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

	/**
	 * @return EditTaskResult
	 * @throws CancelException
	 */
	public function executeInternal(): EditTaskResult
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
				$this->executePart($chunkHandler, $current);
				$this->blocks = [];
				$current = $c;
			}
			$this->blocks[] = BlockVector::fromVector($pos);
		}
		if ($current !== null) {
			$this->executePart($chunkHandler, $current);
		}
		return $this->toTaskResult();
	}

	/**
	 * @param SingleChunkHandler $handler
	 * @param int                $chunk
	 * @return void
	 * @throws CancelException
	 */
	private function executePart(SingleChunkHandler $handler, int $chunk): void
	{
		$handler->request($chunk);
		while ($handler->getNextChunk() === null) {
			EditThread::getInstance()->checkExecution();
			EditThread::getInstance()->waitForData();
		}
		$this->runEdit($chunk, $handler->getData());
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