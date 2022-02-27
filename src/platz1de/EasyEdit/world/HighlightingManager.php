<?php

namespace platz1de\EasyEdit\world;

use platz1de\EasyEdit\utils\PacketUtils;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

class HighlightingManager
{
	/**
	 * @var array<string, array<int, ReferencedPosition>>
	 */
	private static array $staticDataHolders = [];
	/**
	 * @var CompoundTag[]
	 */
	private static array $staticData = [];

	private static int $id = 1;

	/**
	 * Highlight a cube using structure blocks
	 *
	 * @param string  $player
	 * @param string  $world
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param Vector3 $dataHolder
	 * @return int
	 */
	public static function highlightStaticCube(string $player, string $world, Vector3 $pos1, Vector3 $pos2, Vector3 $dataHolder): int
	{
		if (!isset(self::$staticDataHolders[$player])) {
			self::$staticDataHolders[$player] = [];
		}

		self::$staticDataHolders[$player][self::$id] = new ReferencedPosition($dataHolder->floor(), $world);
		self::$staticData[self::$id] = CompoundTag::create()
			->setInt("xStructureOffset", $pos1->getFloorX() - $dataHolder->getFloorX())
			->setInt("yStructureOffset", $pos1->getFloorY() - $dataHolder->getFloorY())
			->setInt("zStructureOffset", $pos1->getFloorZ() - $dataHolder->getFloorZ())
			->setInt("xStructureSize", $pos2->getFloorX() - $pos1->getFloorX() + 1)
			->setInt("yStructureSize", $pos2->getFloorY() - $pos1->getFloorY() + 1)
			->setInt("zStructureSize", $pos2->getFloorZ() - $pos1->getFloorZ() + 1)
			->setByte("showBoundingBox", 1);

		self::sendStaticHolder($player, self::$id);

		return self::$id++;
	}

	/**
	 * @param string $player
	 * @param int    $id
	 */
	private static function sendStaticHolder(string $player, int $id): void
	{
		if (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
			PacketUtils::sendFakeBlock(self::$staticDataHolders[$player][$id], self::$staticDataHolders[$player][$id]->getWorld(), $p, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS, self::$staticData[$id]);
		}
	}

	/**
	 * @param string $player
	 * @param int    $id
	 */
	private static function removeStaticHolder(string $player, int $id): void
	{
		if (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
			//Minecraft doesn't delete BlockData if the original Block shouldn't have some or whole chunks get sent
			PacketUtils::sendFakeBlock(self::$staticDataHolders[$player][$id], $p->getWorld(), $p, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS);
			PacketUtils::resendBlock(self::$staticDataHolders[$player][$id], $p->getWorld(), $p);
		}
	}

	/**
	 * @param string $player
	 * @param int    $id
	 */
	public static function clear(string $player, int $id): void
	{
		if (isset(self::$staticDataHolders[$player][$id])) {
			self::removeStaticHolder($player, $id);
			unset(self::$staticDataHolders[$player][$id], self::$staticData[$id]);
		}
	}

	/**
	 * @param string $player
	 */
	public static function resendAll(string $player): void
	{
		if (isset(self::$staticDataHolders[$player]) && (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player)) {
			foreach (self::$staticDataHolders[$player] as $id => $pos) {
				if ($pos->getWorldName() === $p->getWorld()->getFolderName()) {
					self::sendStaticHolder($player, $id);
				} else {
					self::removeStaticHolder($player, $id);
				}
			}
		}
	}

	/**
	 * Highlight a cube using structure blocks
	 *
	 * @param Player  $player
	 * @param World   $world
	 * @param Vector3 $dataHolder
	 * @param Vector3 $min
	 * @param Vector3 $max
	 * @return int
	 */
	public static function showStructureView(Player $player, World $world, Vector3 $dataHolder, Vector3 $min, Vector3 $max): int
	{
		PacketUtils::sendFakeBlock($dataHolder->floor(), $world, $player, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS, CompoundTag::create()
			->setString("id", "StructureBlock")
			->setString("structureName", "clipboard")
			->setString("dataField", "")
			->setInt("xStructureOffset", $min->getFloorX() - $dataHolder->getFloorX())
			->setInt("yStructureOffset", $min->getFloorY() - $dataHolder->getFloorY())
			->setInt("zStructureOffset", $min->getFloorZ() - $dataHolder->getFloorZ())
			->setInt("xStructureSize", $max->getFloorX() - $min->getFloorX() + 1)
			->setInt("yStructureSize", $max->getFloorY() - $min->getFloorY() + 1)
			->setInt("zStructureSize", $max->getFloorZ() - $min->getFloorZ() + 1)
			->setInt("data", 5)
			->setByte("rotation", 0)
			->setByte("mirror", 0)
			->setFloat("integrity", 100.0)
			->setLong("seed", 0)
			->setByte("ignoreEntities", 1)
			->setByte("includePlayers", 0)
			->setByte("removeBlocks", 0)
			->setByte("showBoundingBox", 0)
			->setByte("isMovable", 0)
			->setByte("isPowered", 0));

		if (($inv = $player->getNetworkSession()->getInvManager()) instanceof InventoryManager) {
			$player->getNetworkSession()->sendDataPacket(ContainerOpenPacket::blockInv($inv->getCurrentWindowId(), WindowTypes::STRUCTURE_EDITOR, new BlockPosition($dataHolder->getFloorX(), $dataHolder->getFloorY(), $dataHolder->getFloorZ())));
		}
		return self::$id++;
	}
}