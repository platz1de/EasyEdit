<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\BlockListSelection;
use pocketmine\level\format\Chunk;
use pocketmine\nbt\tag\CompoundTag;
use Serializable;

class EditTaskResult implements Serializable
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
		foreach ($result->getUndo()->getTiles() as $tile){
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
	public function serialize(): string
	{
		return igbinary_serialize([
			"chunks" => array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $this->manager->getChunks()),
			"level" => $this->manager->getLevelName(),
			"toUndo" => $this->toUndo,
			"tiles" => $this->tiles,
			"time" => $this->time,
			"changed" => $this->changed
		]);
	}

	public function unserialize($data): void
	{
		$dat = igbinary_unserialize($data);

		$this->manager = new ReferencedChunkManager($dat["level"]);
		foreach ($dat["chunks"] as $chunk) {
			$chunk = Chunk::fastDeserialize($chunk);
			$this->manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		$this->toUndo = $dat["toUndo"];
		$this->tiles = $dat["tiles"];
		$this->time = $dat["time"];
		$this->changed = $dat["changed"];
	}

	public function free(): void
	{
		$this->manager->cleanChunks();
		$this->tiles = [];
	}
}