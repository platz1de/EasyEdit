<?php

namespace platz1de\EasyEdit\thread;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

class ChunkCollector
{
	private static bool $ready = false;
	private static ?ReferencedChunkManager $manager;
	/**
	 * @var CompoundTag[]
	 */
	private static array $tiles = [];

	/**
	 * @param string $world
	 */
	public static function init(string $world): void
	{
		self::$manager = new ReferencedChunkManager($world);
	}

	/**
	 * @param int[] $chunks
	 */
	public static function request(array $chunks): void
	{
		if (self::$manager === null) {
			throw new BadMethodCallException("Collector was never initialized");
		}

		foreach ($chunks as $key => $chunk) {
			World::getXZ($chunk, $x, $z);
			if (self::$manager->getChunk($x, $z) !== null) {
				unset($chunks[$key]);
			}
		}

		ChunkRequestData::from($chunks, self::$manager->getWorldName());
	}

	/**
	 * @param ChunkInputData $data
	 */
	public static function collectInput(ChunkInputData $data): void
	{
		if (self::$manager === null) {
			throw new BadMethodCallException("Collector was never initialized");
		}

		self::$ready = true;

		$chunks = new ExtendedBinaryStream($data->getChunkData());
		while (!$chunks->feof()) {
			self::$manager->setChunk($chunks->getInt(), $chunks->getInt(), FastChunkSerializer::deserializeTerrain($chunks->getString()));
		}

		$tiles = new ExtendedBinaryStream($data->getTileData());
		while (!$tiles->feof()) {
			$tile = $tiles->getCompound();
			self::$tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}
	}

	public static function hasReceivedInput(): bool
	{
		return self::$ready;
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public static function getChunks(): ReferencedChunkManager
	{
		if (!self::$ready || self::$manager === null) {
			throw new BadMethodCallException("No chunk inputs were received yet");
		}
		return self::$manager;
	}

	/**
	 * @return CompoundTag[]
	 */
	public static function getTiles(): array
	{
		if (!self::$ready) {
			throw new BadMethodCallException("No chunk inputs were received yet");
		}
		return self::$tiles;
	}

	/**
	 * @param Closure $closure
	 */
	public static function clean(Closure $closure): void
	{
		self::$ready = false;
		self::$manager?->filterChunks($closure);
		EditThread::getInstance()->debug("Cached " . count(self::$manager?->getChunks() ?? []) . " chunks");
		self::$tiles = array_filter(self::$tiles, static function (CompoundTag $tile) {
			return self::$manager?->getChunk($tile->getInt(Tile::TAG_X) >> 4, $tile->getInt(Tile::TAG_Z) >> 4) !== null;
		});
	}

	public static function clear(): void
	{
		self::$ready = false;
		self::$manager = null;
		self::$tiles = [];
	}
}