<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\world\format\Chunk;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;

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
		$stream = new ExtendedBinaryStream();

		$stream->putString($this->manager->getWorldName());

		$chunks = new ExtendedBinaryStream();
		$count = 0;
		foreach ($this->manager->getChunks() as $chunk) {
			$chunks->putString($chunk->fastSerialize());
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$stream->putString($this->toUndo->fastSerialize());

		$stream->putString((string) (new LittleEndianNBTStream())->write($this->tiles));

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
		$stream = new ExtendedBinaryStream($data);

		$level = $stream->getString();

		$chunks = [];
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$chunks[] = Chunk::fastDeserialize($stream->getString());
		}

		/** @var BlockListSelection $undo */
		$undo = Selection::fastDeserialize($stream->getString());

		$tileData = $stream->getString();
		if ($tileData !== "") {
			$tiles = (new LittleEndianNBTStream())->read($tileData, true);
		} else {
			$tiles = [];
		}
		/** @var CompoundTag[] $tiles */
		$tiles = is_array($tiles) ? $tiles : [$tiles];

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