<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\task\queued\QueuedCallbackTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\tile\TileFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use UnexpectedValueException;

class LoaderManager
{
	/**
	 * @param World $level
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @return Chunk
	 */
	public static function getChunk(World $level, int $chunkX, int $chunkZ): Chunk
	{
		if ($level->isChunkLoaded($chunkX, $chunkZ)) {
			$chunk = $level->getChunk($chunkX, $chunkZ);
		} else {
			$chunk = $level->getProvider()->loadChunk($chunkX, $chunkZ);
		}

		if (!$chunk instanceof Chunk) {
			throw new UnexpectedValueException("Could not load chunk " . $chunkX . " " . $chunkZ . ", was it generated first?");
		}

		return $chunk;
	}

	/**
	 * @param World         $level
	 * @param Chunk[]       $chunks
	 * @param CompoundTag[] $tiles
	 * @return void
	 */
	public static function setChunks(World $level, array $chunks, array $tiles): void
	{
		foreach ($tiles as $tile) {
			$tile = TileFactory::getInstance()->createFromData($level, $tile);
			if ($tile !== null) {
				$hash = World::chunkHash($tile->getPosition()->getX() >> 4, $tile->getPosition()->getZ() >> 4);
				if (isset($chunks[$hash])) {
					$chunks[$hash]->addTile($tile);
				}
			}
		}

		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			self::injectChunk($level, $x, $z, $chunk);
		}

		//reduce load by not setting and unloading on the same tick
		WorkerAdapter::priority(new QueuedCallbackTask(function () use ($chunks, $level): void {
			foreach ($chunks as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				$level->unloadChunk($x, $z);
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

		$chunk->setDirty();

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