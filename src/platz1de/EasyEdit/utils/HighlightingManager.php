<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class HighlightingManager
{
	/**
	 * @var array<string, array<int, Position>>
	 */
	private static $staticDataHolders = [];
	/**
	 * @var CompoundTag[]
	 */
	private static $staticData = [];

	/**
	 * @var int
	 */
	private static $id = 1;

	/**
	 * Highlight a cube using structure blocks
	 *
	 * @param string  $player
	 * @param World   $world
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param Vector3 $dataHolder
	 * @return int
	 */
	public static function highlightStaticCube(string $player, World $world, Vector3 $pos1, Vector3 $pos2, Vector3 $dataHolder): int
	{
		if (!isset(self::$staticDataHolders[$player])) {
			self::$staticDataHolders[$player] = [];
		}

		self::$staticDataHolders[$player][self::$id] = Position::fromObject($dataHolder->floor(), $world);
		self::$staticData[self::$id] = CompoundTag::create()
			->setString("structureName", "selection")
			->setString("dataField", "")
			->setInt("xStructureOffset", $pos1->getFloorX() - $dataHolder->getFloorX())
			->setInt("yStructureOffset", $pos1->getFloorY() - $dataHolder->getFloorY())
			->setInt("zStructureOffset", $pos1->getFloorZ() - $dataHolder->getFloorZ())
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
			->setByte("showBoundingBox", 1)
			->setByte("isMovable", 1)
			->setByte("isPowered", 0);

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
			PacketUtils::sendFakeBlock(self::$staticDataHolders[$player][$id], self::$staticDataHolders[$player][$id]->getWorld(), $p, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS);
			PacketUtils::resendBlock(self::$staticDataHolders[$player][$id], self::$staticDataHolders[$player][$id]->getWorld(), $p);
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
				if ($pos->getWorld() === $p->getWorld()) {
					self::sendStaticHolder($player, $id);
				} else {
					self::removeStaticHolder($player, $id);
				}
			}
		}
	}

	/**
	 * @var array{string, World, Vector3, Vector3, float}[]
	 */
	private static $cubes = [];

	public static function refresh(): void
	{
		foreach (self::$cubes as $cube) {
			if (($p = Server::getInstance()->getPlayerExact($cube[0])) instanceof Player) {
				self::cube($p, $cube[1], $cube[2], $cube[3], $cube[4]);
			}
		}
	}

	/**
	 * @param string  $player
	 * @param World   $world
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param float   $step
	 * @return int
	 */
	public static function highlightCube(string $player, World $world, Vector3 $pos1, Vector3 $pos2, float $step = 0.5): int
	{
		self::$cubes[self::$id] = [$player, $world, $pos1, $pos2, $step];
		return self::$id++;
	}

	/**
	 * @param Player  $player
	 * @param World   $world
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @param float   $step
	 */
	private static function cube(Player $player, World $world, Vector3 $start, Vector3 $end, float $step = 0.5): void
	{
		//x-Axis from start
		self::line($player, $world, $start, $sx = new Vector3($end->getX(), $start->getY(), $start->getZ()));
		//y-Axis from start
		self::line($player, $world, $start, $sy = new Vector3($start->getX(), $end->getY(), $start->getZ()));
		//z-Axis from start
		self::line($player, $world, $start, $sz = new Vector3($start->getX(), $start->getY(), $end->getZ()));
		//x-Axis from end
		self::line($player, $world, $end, $ex = new Vector3($start->getX(), $end->getY(), $end->getZ()));
		//y-Axis from end
		self::line($player, $world, $end, $ey = new Vector3($end->getX(), $start->getY(), $end->getZ()));
		//z-Axis from end
		self::line($player, $world, $end, $ez = new Vector3($end->getX(), $end->getY(), $start->getZ()));

		self::line($player, $world, $sx, $ez);
		self::line($player, $world, $sz, $ex);
		self::line($player, $world, $sy, $ex);
		self::line($player, $world, $sy, $ez);
		self::line($player, $world, $ey, $sx);
		self::line($player, $world, $ey, $sz);
	}

	/**
	 * @param Player  $player
	 * @param World   $world
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @param float   $step
	 */
	private static function line(Player $player, World $world, Vector3 $start, Vector3 $end, float $step = 0.5): void
	{
		$min = VectorUtils::getMin($start, $end);
		$max = VectorUtils::getMax($start, $end);
		$direction = $max->subtractVector($min)->normalize()->multiply($step);
		VectorUtils::makeLoopSafe($direction);
		for ($x = $min->getX(); $x <= $max->getX(); $x += $direction->getX()) {
			for ($y = $min->getY(); $y <= $max->getY(); $y += $direction->getY()) {
				for ($z = $min->getZ(); $z <= $max->getZ(); $z += $direction->getZ()) {
					self::dot($player, $world, new Vector3($x, $y, $z));
				}
			}
		}
	}

	/**
	 * @param Player  $player
	 * @param World   $world
	 * @param Vector3 $pos
	 */
	private static function dot(Player $player, World $world, Vector3 $pos): void
	{
		$particle = new FlameParticle();
		$world->addParticle($pos, $particle, [$player]);
	}
}