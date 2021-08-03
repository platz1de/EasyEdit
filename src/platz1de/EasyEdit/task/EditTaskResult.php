<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

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

	public function addChunk(int $x, int $z, Chunk $chunk): void
	{
		$this->manager->setChunk($x, $z, $chunk);
	}

	/**
	 * @param EditTaskResult $result
	 */
	public function merge(EditTaskResult $result): void
	{
		$this->time += $result->getTime();
		$this->changed += $result->getChanged();
		foreach ($result->getUndo()->getManager()->getChunks() as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			//TODO: only create Chunks which are really needed
			if (LoaderManager::isChunkUsed($chunk)) {
				$this->getUndo()->getManager()->setChunk($x, $z, $chunk);
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
		foreach ($this->manager->getChunks() as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$chunks->putInt($x);
			$chunks->putInt($z);
			$chunks->putString(FastChunkSerializer::serializeWithoutLight($chunk));
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$stream->putString($this->toUndo->fastSerialize());

		$stream->putString((new LittleEndianNbtSerializer())->writeMultiple(array_map(static function (CompoundTag $tag): TreeRoot { return new TreeRoot($tag); }, $this->tiles)));

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
			$chunks[World::chunkHash($stream->getInt(), $stream->getInt())] = FastChunkSerializer::deserialize($stream->getString());
		}

		/** @var BlockListSelection $undo */
		$undo = Selection::fastDeserialize($stream->getString());

		$tileData = $stream->getString();
		if ($tileData !== "") {
			$tiles = array_map(static function (TreeRoot $root): CompoundTag { return $root->mustGetCompoundTag(); }, (new LittleEndianNbtSerializer())->readMultiple($tileData));
		} else {
			$tiles = [];
		}

		$time = $stream->getFloat();
		$changed = $stream->getLInt();

		$result = new EditTaskResult($level, $undo, $tiles, $time, $changed);
		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$result->addChunk($x, $z, $chunk);
		}

		return $result;
	}

	public function free(): void
	{
		$this->manager->cleanChunks();
		$this->tiles = [];
	}
}