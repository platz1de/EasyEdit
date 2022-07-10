<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\World;

class LineTask extends EditTask
{
	use SettingNotifier;

	private Vector3 $end;
	private StaticBlock $block;

	/**
	 * @var Vector3[]
	 * These are 48 blocks in the worst case
	 */
	private array $blocks = [];

	/**
	 * @param string                     $world
	 * @param AdditionalDataManager|null $data
	 * @param Vector3                    $start
	 * @param Vector3                    $end
	 * @param StaticBlock                $block
	 * @return LineTask
	 */
	public static function from(string $world, ?AdditionalDataManager $data, Vector3 $start, Vector3 $end, StaticBlock $block): LineTask
	{
		$instance = new self($world, $data ?? new AdditionalDataManager(), $start);
		$instance->end = $end;
		$instance->block = $block;
		return $instance;
	}

	public function execute(): void
	{
		$this->getDataManager()->useFastSet();
		ChunkCollector::init($this->getWorld());
		$current = null;
		//offset points to not yield blocks beyond the endings
		foreach (VoxelRayTrace::betweenPoints($this->getPosition()->add(0.5, 0.5, 0.5), $this->end->add(0.5, 0.5, 0.5)) as $pos) {
			if ($current === null) {
				$current = World::chunkHash($pos->x >> Block::INTERNAL_METADATA_BITS, $pos->z >> Block::INTERNAL_METADATA_BITS);
			} elseif ($current !== ($c = World::chunkHash($pos->x >> Block::INTERNAL_METADATA_BITS, $pos->z >> Block::INTERNAL_METADATA_BITS))) {
				$this->requestChunks([$current]);
				$this->blocks = [];
				$current = $c;
			}
			$this->blocks[] = $pos;
		}
		if ($current !== null) {
			$this->getDataManager()->setFinal();
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
		$current = $this->blocks[0] ?? $this->getPosition();
		return $current->distance($this->end) / $this->getPosition()->distance($this->end);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->end);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->end = $stream->getVector();
		$this->block = new StaticBlock($stream->getInt());
	}
}