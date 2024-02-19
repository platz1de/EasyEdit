<?php

namespace platz1de\EasyEdit\listener;

use platz1de\EasyEdit\utils\PacketUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPostChunkSendEvent;
use pocketmine\math\Vector3;
use pocketmine\utils\SingletonTrait;

/**
 * Small history lesson:
 * In 1.16 Mojang somehow screwed up chunk updates, so they render about 5 seconds delayed
 * Note that this only affects updates, not the initial chunk loading or single block updates
 *
 * Since we need to update chnuks and don't want to wait 5 seconds, we need to force the client to update the chunk
 * This is done by sending a fake block in the chunk after the clients receives the chunk, which instantly causes a rerender of the chunk
 * Sadly this still produces an ugly flickering effect, but all known ways to avoid this include sending each block individually,
 * this is already done for smaller edits (@see InjectingSubChunkController) but for larger edits this is simply not feasible (it also triples execution time)
 * Ty Mojang
 */
class ChunkRefreshListener implements Listener
{
	use SingletonTrait;

	/**
	 * @var array<string, int>
	 */
	private array $affectedPlayers = [];

	public function onChunkSend(PlayerPostChunkSendEvent $event): void
	{
		$player = $event->getPlayer();
		$playerName = $player->getName();
		if (isset($this->affectedPlayers[$playerName]) && $this->affectedPlayers[$playerName] > time()) {
			PacketUtils::sendFakeBlockAt($player, new Vector3($event->getChunkX() << 4, (int) $player->getPosition()->getY(), $event->getChunkZ() << 4), VanillaBlocks::GOLD());
			PacketUtils::resendBlock(new Vector3($event->getChunkX() << 4, (int) $player->getPosition()->getY(), $event->getChunkZ() << 4), $player->getWorld(), $player);
		}
	}

	/**
	 * Request chunk refreshes for the next 5 seconds
	 * @param string $playerName
	 */
	public function addAffectedPlayer(string $playerName): void
	{
		$this->affectedPlayers[$playerName] = time() + 5;
	}

	public function clearPlayer(string $playerName): void
	{
		unset($this->affectedPlayers[$playerName]);
	}
}