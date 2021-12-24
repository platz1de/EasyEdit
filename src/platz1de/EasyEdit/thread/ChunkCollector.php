<?php

namespace platz1de\EasyEdit\thread;

use BadMethodCallException;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use platz1de\EasyEdit\thread\input\ChunkInputData;
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
	private static array $tiles;

	/**
	 * @param string $world
	 */
	public static function init(string $world): void
	{
		self::$manager = new ReferencedChunkManager($world);
	}

	/**
	 * @param ChunkInputData $data
	 */
	public static function collectInput(ChunkInputData $data): void
	{
		self::$ready = true;

		if (self::$manager === null) {
			throw new BadMethodCallException("Collector was never initialized");
		}

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

	public static function clean(): void
	{
		self::$ready = false;
		self::$manager?->cleanChunks();
		self::$tiles = [];
	}

	public static function clear(): void
	{
		self::$ready = false;
		self::$manager = null;
		self::$tiles = [];
	}
}