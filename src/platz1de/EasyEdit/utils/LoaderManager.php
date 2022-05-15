<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\world\blockupdate\UpdateSubChunkBlocksInjector;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
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
	 * @param World              $world
	 * @param ChunkInformation[] $chunks
	 * @param string[]           $injections
	 */
	public static function setChunks(World $world, array $chunks, array $injections): void
	{
		$preparedInjections = [];
		foreach ($injections as $hash => $injection) {
			World::getBlockXYZ($hash, $x, $y, $z);
			$preparedInjections[$x][$z][$y] = $injection;
		}

		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			self::injectChunk($world, $x, $z, $chunk, $preparedInjections[$x][$z] ?? []);
			$world->unloadChunk($x, $z);
		}
	}

	/**
	 * Implementation of World::setChunk without loading unnecessary Chunks which get overwritten anyways
	 * @param World            $world
	 * @param int              $x
	 * @param int              $z
	 * @param ChunkInformation $chunkInformation
	 * @param string[]         $preparedInjections
	 * @see          World::setChunk()
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public static function injectChunk(World $world, int $x, int $z, ChunkInformation $chunkInformation, array $preparedInjections): void
	{
		$chunk = $chunkInformation->getChunk();
		$chunkHash = World::chunkHash($x, $z);

		//TODO: this deletes entities in unloaded chunks (load entities to EditThread)
		if ($world->isChunkLoaded($x, $z)) {
			$old = $world->getChunk($x, $z);
			if ($old !== null) {
				foreach ($old->getTiles() as $tile) {
					$tile->close();
				}
			}
		}

		$chunk->setTerrainDirty();

		(function () use ($preparedInjections, $z, $x, $chunkHash, $chunk): void {
			$this->chunks[$chunkHash] = $chunk;

			unset($this->blockCache[$chunkHash], $this->changedBlocks[$chunkHash]);

			foreach ($this->getChunkListeners($x, $z) as $loader) {
				//In 1.16 Mojang really ruined Chunk updates, normal block rendering is delayed by about 1-5 seconds
				if ($loader instanceof Player && $preparedInjections !== []) {
					foreach ($preparedInjections as $injection) {
						//Hack to allow instant block setting, costly network wise
						$loader->getNetworkSession()->sendDataPacket(UpdateSubChunkBlocksInjector::create($injection));
					}
				} else {
					$loader->onChunkChanged($x, $z, $chunk);
				}
			}
		})->call($world);

		foreach ($chunkInformation->getTiles() as $rawTile) {
			$tile = TileFactory::getInstance()->createFromData($world, $rawTile);
			if ($tile !== null) {
				$chunk->addTile($tile);
			}
		}

		if ($preparedInjections !== []) {
			foreach ($chunkInformation->getTiles() as $tile) {
				foreach ($world->getChunkPlayers($tile->getInt(Tile::TAG_X) >> 4, $tile->getInt(Tile::TAG_Z) >> 4) as $player) {
					PacketUtils::resendBlock(new Vector3($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z)), $world, $player);
				}
			}
		}
	}

	/**
	 * @param ChunkInformation $chunk
	 * @return bool
	 */
	public static function isChunkUsed(ChunkInformation $chunk): bool
	{
		foreach ($chunk->getChunk()->getSubChunks() as $subChunk) {
			if (!$subChunk->isEmptyFast()) {
				return true;
			}
		}

		return false;
	}
}