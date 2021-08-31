<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\task\queued\QueuedCallbackTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\tile\TileFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;
use UnexpectedValueException;

class LoaderManager
{
	/**
	 * @param World $world
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @return Chunk|ChunkData
	 */
	public static function getChunk(World $world, int $chunkX, int $chunkZ): Chunk|ChunkData
	{
		if ($world->isChunkLoaded($chunkX, $chunkZ)) {
			$chunk = $world->getChunk($chunkX, $chunkZ);
		} else {
			$chunk = $world->getProvider()->loadChunk($chunkX, $chunkZ);
		}

		if (!$chunk instanceof Chunk && !$chunk instanceof ChunkData) {
			throw new UnexpectedValueException("Could not load chunk " . $chunkX . " " . $chunkZ . ", was it generated first?");
		}

		return $chunk;
	}

	/**
	 * @param World         $world
	 * @param Chunk[]       $chunks
	 * @param CompoundTag[] $tiles
	 * @return void
	 */
	public static function setChunks(World $world, array $chunks, array $tiles): void
	{
		foreach ($tiles as $tile) {
			$tile = TileFactory::getInstance()->createFromData($world, $tile);
			if ($tile !== null) {
				$hash = World::chunkHash($tile->getPosition()->getX() >> 4, $tile->getPosition()->getZ() >> 4);
				if (isset($chunks[$hash])) {
					$chunks[$hash]->addTile($tile);
				}
			}
		}

		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			self::injectChunk($world, $x, $z, $chunk);
		}

		//reduce load by not setting and unloading on the same tick
		WorkerAdapter::priority(new QueuedCallbackTask(function () use ($chunks, $world): void {
			foreach ($chunks as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				$world->unloadChunk($x, $z);
			}
		}));
	}

	/**
	 * Implementation of World::setChunk without loading unnecessary Chunks which get overwritten anyways
	 * @param World $world
	 * @param int   $x
	 * @param int   $z
	 * @param Chunk $chunk
	 * @see          World::setChunk()
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public static function injectChunk(World $world, int $x, int $z, Chunk $chunk): void
	{
		$chunkHash = World::chunkHash($x, $z);

		//TODO: this deletes entities in unloaded chunks (load entities to EditThread)
		if ($world->isChunkLoaded($x, $z)) {
			$old = $world->getChunk($x, $z);
			if ($old !== null) {
				foreach ($old->getTiles() as $tile) {
					$tile->close();
				}
				foreach ($old->getEntities() as $entity) {
					$chunk->addEntity($entity);
					$old->removeEntity($entity);
				}
			}
		}

		$chunk->setTerrainDirty();

		(function () use ($z, $x, $chunkHash, $chunk): void {
			$this->chunks[$chunkHash] = $chunk;

			unset($this->blockCache[$chunkHash], $this->changedBlocks[$chunkHash]);

			foreach ($this->getChunkListeners($x, $z) as $loader) {
				$loader->onChunkChanged($x, $z, $chunk);
			}
		})->call($world);

		//TODO: In 1.17 Mojang really ruined Chunk updates, block rendering is delayed by about 1-5 seconds
	}

	/**
	 * @param Chunk $chunk
	 * @return bool
	 */
	public static function isChunkUsed(Chunk $chunk): bool
	{
		foreach ($chunk->getSubChunks() as $subChunk) {
			if (!$subChunk->isEmptyFast()) {
				return true;
			}
		}

		return false;
	}
}