<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class StructureBlockOutline extends ClientSideBlock
{
	use ReferencedWorldHolder;

	public CompoundBlock $block;
	public Vector3 $startPosition;
	public Vector3 $endPosition;

	public function __construct(string $world, Vector3 $pos1, Vector3 $pos2)
	{
		$this->startPosition = $pos1;
		$this->endPosition = $pos2;
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
			->setInt("xStructureOffset", $pos1->getFloorX() - $player->getPosition()->getFloorX())
			->setInt("yStructureOffset", $pos1->getFloorY() - World::Y_MIN)
			->setInt("zStructureOffset", $pos1->getFloorZ() - $player->getPosition()->getFloorZ())
			->setInt("xStructureSize", $pos2->getFloorX() - $pos1->getFloorX() + 1)
			->setInt("yStructureSize", $pos2->getFloorY() - $pos1->getFloorY() + 1)
			->setInt("zStructureSize", $pos2->getFloorZ() - $pos1->getFloorZ() + 1);
	}
}