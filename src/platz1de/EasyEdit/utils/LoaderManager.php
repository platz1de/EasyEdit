<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\world\blockupdate\UpdateSubChunkBlocksInjector;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
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
				if ($loader instanceof Player) {
					if ($preparedInjections !== []) {
						foreach ($preparedInjections as $injection) {
							//Hack to allow instant, flicker-free block setting, costly network wise
							$loader->getNetworkSession()->sendDataPacket(UpdateSubChunkBlocksInjector::create($injection));
						}
					} else {
						//Hack to significantly reduce the delay of block updates, sadly it does not remove the flickering
						$loader->getNetworkSession()->sendDataPacket(LevelChunkPacket::create(new ChunkPosition($x, $z), ChunkSerializer::getSubChunkCount($chunk, DimensionIds::OVERWORLD), false, null, ChunkSerializer::serializeFullChunk($chunk, TypeConverter::getInstance()->getBlockTranslator(), new PacketSerializerContext(TypeConverter::getInstance()->getItemTypeDictionary()))));
						//force reload of chunk
						PacketUtils::sendFakeBlockAt($loader, new Vector3($x << 4, (int) $loader->getPosition()->getY(), $z << 4), $chunk->getBlockStateId($x << 4, (int) $loader->getPosition()->getY(), $z << 4) >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::GOLD ? VanillaBlocks::RAW_GOLD() : VanillaBlocks::GOLD());
						PacketUtils::resendBlock(new Vector3($x << 4, (int) $loader->getPosition()->getY(), $z << 4), $this, $loader);
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
				foreach ($world->getChunkPlayers($tile->getPosition()->getX() >> 4, $tile->getPosition()->getZ() >> 4) as $player) {
					if ($preparedInjections === []) {
						PacketUtils::sendFakeBlockAt($player, $tile->getPosition(), VanillaBlocks::AIR());
					}
					PacketUtils::resendBlock($tile->getPosition(), $world, $player);
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
