<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\world\clientblock\CompoundBlock;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\world\World;

class PacketUtils
{
	/**
	 * @param Player $player
	 * @param Block  $block
	 * @param bool   $ignoreData
	 */
	public static function sendFakeBlock(Player $player, Block $block, bool $ignoreData = false): void
	{
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
			BlockPosition::fromVector3($block->getPosition()),
			RuntimeBlockMapping::getInstance()->toRuntimeId($block->getStateId()),
			UpdateBlockPacket::FLAG_NETWORK,
			UpdateBlockPacket::DATA_LAYER_NORMAL
		));

		if (!$ignoreData && $block instanceof CompoundBlock) {
			$player->getNetworkSession()->sendDataPacket(BlockActorDataPacket::create(BlockPosition::fromVector3($block->getPosition()), new CacheableNbt($block->getData())));
		}
	}

	/**
	 * @param Vector3 $vector
	 * @param World   $world
	 * @param Player  $player
	 */
	public static function resendBlock(Vector3 $vector, World $world, Player $player): void
	{
		foreach ($world->createBlockUpdatePackets([$vector]) as $packet) {
			$player->getNetworkSession()->sendDataPacket($packet);
		}
	}
}