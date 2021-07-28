<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\block\BlockFactory;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

/**
 * We don't want to use packets directly in any way
 */
class PacketUtils
{
	/**
	 * @param Vector3          $vector
	 * @param World            $world
	 * @param Player           $player
	 * @param int              $block
	 * @param CompoundTag|null $data
	 */
	public static function sendFakeBlock(Vector3 $vector, World $world, Player $player, int $block, ?CompoundTag $data = null): void
	{
		//construct fake data
		/** @var ?Tile $prev */
		$prev = (function () use ($data, $block, $vector): ?Tile {
			$this->blockCache[World::chunkHash($vector->x >> 4, $vector->z >> 4)][World::chunkBlockHash($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorZ())] = BlockFactory::getInstance()->fromFullBlock($block);
			$prev = $this->getTile($vector);
			$chunk = $this->getChunk($vector->x >> 4, $vector->z >> 4);
			if ($chunk instanceof Chunk) {
				if ($prev instanceof Tile) {
					$chunk->removeTile($prev);
				}
				if ($data instanceof CompoundTag) {
					/** @noinspection PhpParamsInspection */
					$chunk->addTile(new CompoundTile($this, $vector, $data));
				}
			}
			return $prev;
		})->call($world);

		self::resendBlock($vector, $world, $player);

		//restore data
		(function () use ($prev, $vector): void {
			unset($this->blockCache[World::chunkHash($vector->x >> 4, $vector->z >> 4)][World::chunkBlockHash($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorZ())]);
			$fake = $this->getTile($vector);
			$chunk = $this->getChunk($vector->x >> 4, $vector->z >> 4);
			if ($chunk instanceof Chunk) {
				if ($fake instanceof Tile) {
					$chunk->removeTile($fake);
				}
				if ($prev instanceof Tile) {
					$chunk->addTile($prev);
				}
			}
		})->call($world);
	}

	/**
	 * @param Vector3 $vector
	 * @param World   $world
	 * @param Player  $player
	 */
	public static function resendBlock(Vector3 $vector, World $world, Player $player): void
	{
		Server::getInstance()->broadcastPackets([$player], $world->createBlockUpdatePackets([$vector]));
	}
}