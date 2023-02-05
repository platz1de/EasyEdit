<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\world\ReferencedPosition;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;

class StructureBlockWindow extends ClientSideBlock
{
	public CompoundTag $data;
	public ReferencedPosition $position;

	public function __construct(Player $player, Vector3 $pos1, Vector3 $pos2)
	{
		$this->position = new ReferencedPosition($player->getPosition()->up(3)->floor(), $player->getWorld()->getFolderName());
		$this->data = CompoundTag::create()
			->setString("id", "StructureBlock")
			->setString("structureName", "clipboard")
			->setString("dataField", "")
			->setInt("xStructureOffset", $pos1->getFloorX() - $this->position->getFloorX())
			->setInt("yStructureOffset", $pos1->getFloorY() - $this->position->getFloorY())
			->setInt("zStructureOffset", $pos1->getFloorZ() - $this->position->getFloorZ())
			->setInt("xStructureSize", $pos2->getFloorX() - $pos1->getFloorX() + 1)
			->setInt("yStructureSize", $pos2->getFloorY() - $pos1->getFloorY() + 1)
			->setInt("zStructureSize", $pos2->getFloorZ() - $pos1->getFloorZ() + 1)
			->setInt("data", 5)
			->setByte("rotation", 0)
			->setByte("mirror", 0)
			->setFloat("integrity", 100.0)
			->setLong("seed", 0)
			->setByte("ignoreEntities", 1)
			->setByte("includePlayers", 0)
			->setByte("removeBlocks", 0)
			->setByte("showBoundingBox", 0);

		parent::__construct();
	}

	public function send(Player $player): void
	{
		PacketUtils::sendFakeBlock($this->position, $this->position->getWorld(), $player, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_STATE_DATA_BITS, $this->data);
		if (($inv = $player->getNetworkSession()->getInvManager()) instanceof InventoryManager) {
			$player->getNetworkSession()->sendDataPacket(ContainerOpenPacket::blockInv($inv->getCurrentWindowId(), WindowTypes::STRUCTURE_EDITOR, new BlockPosition($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ())));
		}
	}

	public function remove(Player $player): void
	{
		PacketUtils::resendBlock($this->position, $player->getWorld(), $player);
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