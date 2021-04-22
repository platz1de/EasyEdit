<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
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
	public static function setChunks(Level $level, array $chunks, array $tiles): void
	{
		foreach ($chunks as $chunk) {
			if ($level->isChunkLoaded($chunk->getX(), $chunk->getZ())) {
				$old = $level->getChunk($chunk->getX(), $chunk->getZ());
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

			$level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		foreach ($tiles as $tile) {
			Tile::createTile($tile->getString(Tile::TAG_ID), $level, $tile);
		}

		foreach ($chunks as $chunk) {
			$level->unloadChunk($chunk->getX(), $chunk->getZ());
		}
	}
}