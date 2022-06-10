<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\world\ReferencedPosition;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;

class StructureBlockOutline extends ClientSideBlock
{
	public CompoundTag $data;
	public ReferencedPosition $currentPosition;
	public Vector3 $startPosition;
	public Vector3 $endPosition;

	public function __construct(string $world, Vector3 $pos1, Vector3 $pos2)
	{
		$this->startPosition = $pos1;
		$this->endPosition = $pos2;
		$this->currentPosition = new ReferencedPosition($pos1->floor(), $world); //dummy value to carry world name

		parent::__construct();
	}

	public function send(Player $player): void
	{
		$this->recalculatePosition($player);
		PacketUtils::sendFakeBlock($this->currentPosition, $this->currentPosition->getWorld(), $player, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS, $this->data);
	}

	public function remove(Player $player): void
	{
		//Minecraft doesn't delete BlockData if the original Block shouldn't have some or whole chunks get sent
		PacketUtils::sendFakeBlock($this->currentPosition, $player->getWorld(), $player, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS);
		PacketUtils::resendBlock($this->currentPosition, $player->getWorld(), $player);
	}

	public function checkResend(Player $player): void
	{
		if ($this->currentPosition->getWorldName() === $player->getWorld()->getFolderName()) {
			$this->send($player); //TODO: wait for chunk to be loaded
		} else {
			$this->remove($player);
		}
	}

	public function update(Player $player): void
	{
		if ($this->currentPosition->getWorldName() === $player->getWorld()->getFolderName() && !$player->isUsingChunk($this->currentPosition->getX() >> 4, $this->currentPosition->getZ() >> 4)) {
			$this->remove($player);
			$this->send($player);
		}
	}

	public function recalculatePosition(Player $player): void
	{
		$pos1 = $this->startPosition;
		$pos2 = $this->endPosition;
		$this->currentPosition = new ReferencedPosition($player->getPosition()->floor()->withComponents(null, World::Y_MIN, null), $this->currentPosition->getWorldName());
		$this->data = CompoundTag::create()
			->setInt("xStructureOffset", $pos1->getFloorX() - $player->getPosition()->getFloorX())
			->setInt("yStructureOffset", $pos1->getFloorY() - World::Y_MIN)
			->setInt("zStructureOffset", $pos1->getFloorZ() - $player->getPosition()->getFloorZ())
			->setInt("xStructureSize", $pos2->getFloorX() - $pos1->getFloorX() + 1)
			->setInt("yStructureSize", $pos2->getFloorY() - $pos1->getFloorY() + 1)
			->setInt("zStructureSize", $pos2->getFloorZ() - $pos1->getFloorZ() + 1)
			->setByte("showBoundingBox", 1);
	}
}