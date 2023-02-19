<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;

class StructureBlockWindow extends ClientSideBlock
{
	use ReferencedWorldHolder;

	public CompoundBlock $block;

	public function __construct(Player $player, Vector3 $pos1, Vector3 $pos2)
	{
		$this->block = Registry::WINDOW_BLOCK();
		$x = $player->getPosition()->getFloorX();
		$y = $player->getPosition()->getFloorY() + 3;
		$z = $player->getPosition()->getFloorZ();
		//TODO: Always spawn behind the player
		//TODO: height limits (player might not be able to interact with any blocks -> send warning / teleport player?)

		$this->block->position($player->getWorld(), $x, $y, $z);
		$this->block->getData()
			->setInt("xStructureOffset", $pos1->getFloorX() - $x)
			->setInt("yStructureOffset", $pos1->getFloorY() - $y)
			->setInt("zStructureOffset", $pos1->getFloorZ() - $z)
			->setInt("xStructureSize", $pos2->getFloorX() - $pos1->getFloorX() + 1)
			->setInt("yStructureSize", $pos2->getFloorY() - $pos1->getFloorY() + 1)
			->setInt("zStructureSize", $pos2->getFloorZ() - $pos1->getFloorZ() + 1);

		parent::__construct();
	}

	public function send(Player $player): void
	{
		PacketUtils::sendFakeBlock($player, $this->block);
		if (($inv = $player->getNetworkSession()->getInvManager()) instanceof InventoryManager) {
			$player->getNetworkSession()->sendDataPacket(ContainerOpenPacket::blockInv($inv->getCurrentWindowId(), WindowTypes::STRUCTURE_EDITOR, BlockPosition::fromVector3($this->block->getPosition())));
		}
	}

	public function remove(Player $player): void
	{
		PacketUtils::resendBlock($this->block->getPosition(), $player->getWorld(), $player);
	}

	public function checkResend(Player $player): void
	{
		ClientSideBlockManager::unregisterBlock($player->getName(), $this->getId());
	}

	public function update(Player $player): void
	{
		ClientSideBlockManager::unregisterBlock($player->getName(), $this->getId());
	}
}