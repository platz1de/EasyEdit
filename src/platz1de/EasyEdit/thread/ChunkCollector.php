<?php

namespace platz1de\EasyEdit\thread;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\world\World;
use UnexpectedValueException;

class ChunkCollector
{
	private static bool $ready = false;
	private static ?ReferencedChunkManager $manager;

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
			try {
				self::$manager->getChunk($chunk);
				unset($chunks[$key]);
			} catch (UnexpectedValueException) {
				//Chunk needs to be loaded
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
			self::$manager->setChunk(World::chunkHash($chunks->getInt(), $chunks->getInt()), ChunkInformation::readFrom($chunks));
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
	 * @param Closure $closure
	 */
	public static function clean(Closure $closure): void
	{
		self::$ready = false;
		self::$manager?->filterChunks($closure);
		EditThread::getInstance()->debug("Cached " . count(self::$manager?->getChunks() ?? []) . " chunks");
	}

	public static function clear(): void
	{
		self::$ready = false;
		self::$manager = null;
	}
}