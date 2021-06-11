<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use pocketmine\level\format\Chunk;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\BinaryStream;

class EditTaskResult
{
	/**
	 * @var ReferencedChunkManager
	 */
	private $manager;
	/**
	 * @var BlockListSelection
	 */
	private $toUndo;
	/**
	 * @var CompoundTag[]
	 */
	private $tiles;
	/**
	 * @var float
	 */
	private $time;
	/**
	 * @var int
	 */
	private $changed;

	/**
	 * EditTaskResult constructor.
	 * @param string             $level
	 * @param BlockListSelection $toUndo
	 * @param CompoundTag[]      $tiles
	 * @param float              $time
	 * @param int                $changed
	 */
	public function __construct(string $level, BlockListSelection $toUndo, array $tiles, float $time, int $changed)
	{
		$this->manager = new ReferencedChunkManager($level);
		$this->toUndo = $toUndo;
		$this->tiles = $tiles;
		$this->time = $time;
		$this->changed = $changed;
	}

	public function addChunk(Chunk $chunk): void
	{
		$this->manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
	}

	/**
	 * @param EditTaskResult $result
	 */
	public function merge(EditTaskResult $result): void
	{
		$this->time += $result->getTime();
		$this->changed += $result->getChanged();
		foreach ($result->getUndo()->getManager()->getChunks() as $chunk) {
			if ($chunk->getHighestSubChunkIndex() !== -1) {
				$this->getUndo()->getManager()->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
			}
		}
		foreach ($result->getUndo()->getTiles() as $tile) {
			$this->getUndo()->addTile($tile);
		}
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->manager;
	}

	/**
	 * @return BlockListSelection
	 */
	public function getUndo(): BlockListSelection
	{
		return $this->toUndo;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
	}

	/**
	 * @return float
	 */
	public function getTime(): float
	{
		return $this->time;
	}

	/**
	 * @return int
	 */
	public function getChanged(): int
	{
		return $this->changed;
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new BinaryStream();

		$stream->putInt(strlen($this->manager->getLevelName()));
		$stream->put($this->manager->getLevelName());

		$chunks = new BinaryStream();
		$count = 0;
		foreach ($this->manager->getChunks() as $chunk) {
			$c = $chunk->fastSerialize();
			$chunks->putInt(strlen($c));
			$chunks->put($c);
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$undo = $this->toUndo->fastSerialize();
		$stream->putInt(strlen($undo));
		$stream->put($undo);

		//TODO: Test if this need to be serialized otherwise
		$tiles = igbinary_serialize($this->tiles);
		$stream->putInt(strlen($tiles));
		$stream->put($tiles);

		$stream->putFloat($this->time);
		$stream->putLInt($this->changed);

		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return EditTaskResult
	 */
	public static function fastDeserialize(string $data): EditTaskResult
	{
		$stream = new BinaryStream($data);

		$level = $stream->get($stream->getInt());

		$chunks = [];
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$chunks[] = Chunk::fastDeserialize($stream->get($stream->getInt()));
		}

		/** @var BlockListSelection $undo */
		$undo = Selection::fastDeserialize($stream->get($stream->getInt()));

		//TODO: Test if this need to be deserialized otherwise
		$tiles = igbinary_unserialize($stream->get($stream->getInt()));

		$time = $stream->getFloat();
		$changed = $stream->getLInt();

		$result = new EditTaskResult($level, $undo, $tiles, $time, $changed);
		foreach ($chunks as $chunk) {
			$result->addChunk($chunk);
		}

		return $result;
	}

	public function free(): void
	{
		$this->manager->cleanChunks();
		$this->tiles = [];
	}
}