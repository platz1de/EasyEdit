<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

class EditTaskResult
{
	private ReferencedChunkManager $manager;
	/**
	 * @var CompoundTag[]
	 */
	private array $tiles;
	private float $time;
	private int $changed;
	private int $changeId = -1; //only on main-thread

	/**
	 * EditTaskResult constructor.
	 * @param string        $world
	 * @param CompoundTag[] $tiles
	 * @param float         $time
	 * @param int           $changed
	 */
	public function __construct(string $world, array $tiles, float $time, int $changed)
	{
		$this->manager = new ReferencedChunkManager($world);
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
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->manager;
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
	 * @return int
	 */
	public function getChangeId(): int
	{
		return $this->changeId;
	}

	/**
	 * @param int $changeId
	 * @return int
	 */
	public function setChangeId(int $changeId): int
	{
		return $this->changeId = $changeId;
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

		$stream->putCompounds($this->tiles);

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

		$world = $stream->getString();

		$chunks = [];
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$chunks[World::chunkHash($stream->getInt(), $stream->getInt())] = FastChunkSerializer::deserialize($stream->getString());
		}

		$tiles = $stream->getCompounds();

		$time = $stream->getFloat();
		$changed = $stream->getLInt();

		$result = new EditTaskResult($world, $tiles, $time, $changed);
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