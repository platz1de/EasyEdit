<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\task\queued\QueuedCallbackTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\SubChunkInterface;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;

class LoaderManager
{
	/**
	 * @param Level $level
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @return Chunk
	 */
	public static function getChunk(Level $level, int $chunkX, int $chunkZ): Chunk
	{
		if ($level->isChunkLoaded($chunkX, $chunkZ)) {
			$chunk = $level->getChunk($chunkX, $chunkZ);
		} else {
			$chunk = $level->getProvider()->loadChunk($chunkX, $chunkZ);
		}

		if ($chunk === null) {
			$chunk = new Chunk($chunkX, $chunkZ);
		}

		return $chunk;
	}

	/**
	 * @param Level         $level
	 * @param Chunk[]       $chunks
	 * @param CompoundTag[] $tiles
	 * @return void
	 */
	public static function setChunks(World $level, array $chunks, array $tiles): void
	{
		foreach ($chunks as $chunk) {
			self::injectChunk($level, $chunk);
		}

		foreach ($tiles as $tile) {
			Tile::createTile($tile->getString(Tile::TAG_ID), $level, $tile);
		}

		//reduce load by not setting and unloading on the same tick
		WorkerAdapter::priority(new QueuedCallbackTask(function () use ($chunks, $level): void {
			foreach ($chunks as $chunk) {
				$level->unloadChunk($chunk->getX(), $chunk->getZ());
			}
		}));
	}

	/**
	 * Implementation of Level::setChunk without loading unnecessary Chunks which get overwritten anyways
	 * @param Level $level
	 * @param Chunk $chunk
	 * @see Level::setChunk()
	 */
	public static function injectChunk(Level $level, Chunk $chunk): void
	{
		$chunkHash = Level::chunkHash($chunk->getX(), $chunk->getZ());

		//TODO: this deletes entities in unloaded chunks (load entities to EditThread)
		if ($level->isChunkLoaded($chunk->getX(), $chunk->getZ())) {
			$old = $level->getChunk($chunk->getX(), $chunk->getZ(), false);
			if ($old !== null) {
				foreach ($old->getTiles() as $tile) {
					$tile->close();
				}
				foreach ($old->getEntities() as $entity) {
					$chunk->addEntity($entity);
					$old->removeEntity($entity);
					$entity->chunk = $chunk;
				}
			}
		}

		$chunk->setPopulated();
		$chunk->setGenerated();
		$chunk->initChunk($level);

		(function () use ($chunkHash, $chunk): void {
			$this->chunks[$chunkHash] = $chunk;

			unset($this->blockCache[$chunkHash], $this->chunkCache[$chunkHash], $this->changedBlocks[$chunkHash]);

			if (isset($this->chunkSendTasks[$chunkHash])) { //invalidate pending caches
				$this->chunkSendTasks[$chunkHash]->cancelRun();
				unset($this->chunkSendTasks[$chunkHash]);
			}

			foreach ($this->getChunkLoaders($chunk->getX(), $chunk->getZ()) as $loader) {
				$loader->onChunkChanged($chunk);
			}
		})->call($level);

		$chunk->setChanged();

		//TODO: In 1.17 Mojang really ruined Chunk updates, block rendering is delayed by about 1-5 seconds
	}

	/**
	 * @param Chunk $chunk
	 * @return Chunk
	 */
	public static function cloneChunk(Chunk $chunk): Chunk
	{
		$new = new Chunk($chunk->getX(), $chunk->getZ(), array_map(static function (SubChunkInterface $subchunk): SubChunkInterface { return clone $subchunk; }, $chunk->getSubChunks()->toArray()), [], [], $chunk->getBiomeIdArray(), $chunk->getHeightMapArray());
		$new->setGenerated($chunk->isGenerated());
		$new->setPopulated($chunk->isPopulated());
		$new->setLightPopulated($chunk->isLightPopulated());
		$new->setChanged(false);
		return $new;
	}

	/**
	 * @param Chunk $chunk
	 * @return bool
	 */
	public static function isChunkInit(Chunk $chunk): bool
	{
		return (function (): bool {
			return $this->isInit;
		})->call($chunk);
	}
}