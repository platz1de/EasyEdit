<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\player\Player;
use pocketmine\world\World;

class StructureBlockOutline extends ClientSideBlock
{
	use ReferencedWorldHolder;

	public CompoundBlock $block;

	/**
	 * @param string      $world
	 * @param BlockVector $startPosition
	 * @param BlockVector $endPosition
	 */
	public function __construct(string $world, private BlockVector $startPosition, private BlockVector $endPosition)
	{
		$this->world = $world;
		parent::__construct();
	}

	public function send(Player $player): void
	{
		$this->recalculatePosition($player);
		PacketUtils::sendFakeBlock($player, $this->block);
	}

	public function remove(Player $player): void
	{
		//Minecraft doesn't delete BlockData if the original Block shouldn't have some or whole chunks get sent
		PacketUtils::sendFakeBlock($player, $this->block, true);
		PacketUtils::resendBlock($this->block->getPosition(), $player->getWorld(), $player);
	}

	public function checkResend(Player $player): void
	{
		if ($this->getWorldName() === $player->getWorld()->getFolderName()) {
			$this->send($player);
		}
	}

	public function update(Player $player): void
	{
		if ($this->getWorldName() === $player->getWorld()->getFolderName() && !$player->isUsingChunk($this->block->getPosition()->getX() >> 4, $this->block->getPosition()->getZ() >> 4)) {
			$this->remove($player);
			$this->send($player);
		}
	}

	public function recalculatePosition(Player $player): void
	{
		$pos1 = $this->startPosition;
		$pos2 = $this->endPosition;
		$this->block = Registry::OUTLINE_BLOCK();
		$this->block->position($player->getWorld(), $player->getPosition()->getFloorX(), World::Y_MIN, $player->getPosition()->getFloorZ());
		$this->block->getData()
			->setInt("xStructureOffset", $pos1->x - $player->getPosition()->getFloorX())
			->setInt("yStructureOffset", $pos1->y - World::Y_MIN)
			->setInt("zStructureOffset", $pos1->z - $player->getPosition()->getFloorZ())
			->setInt("xStructureSize", $pos2->x - $pos1->x + 1)
			->setInt("yStructureSize", $pos2->y - $pos1->y + 1)
			->setInt("zStructureSize", $pos2->z - $pos1->z + 1);
	}
}