<?php

namespace platz1de\EasyEdit\selection;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\cubic\CubicIterator;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use UnexpectedValueException;

class Cube extends Selection implements Patterned
{
	use CubicIterator;
	use CubicChunkLoader;

	/**
	 * @var Vector3
	 */
	private $structure;

	/**
	 * Cube constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param null|Vector3 $pos1
	 * @param null|Vector3 $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, bool $piece = false)
	{
		$this->structure = new Vector3(0, 0, 0);

		parent::__construct($player, $level, $pos1, $pos2, $piece);
	}

	public function update(): void
	{
		if ($this->isValid()) {
			$minX = min($this->pos1->getX(), $this->pos2->getX());
			$maxX = max($this->pos1->getX(), $this->pos2->getX());
			$minY = max(min($this->pos1->getY(), $this->pos2->getY()), 0);
			$maxY = min(max($this->pos1->getY(), $this->pos2->getY()), World::Y_MAX - 1);
			$minZ = min($this->pos1->getZ(), $this->pos2->getZ());
			$maxZ = max($this->pos1->getZ(), $this->pos2->getZ());

			$this->pos1 = new Vector3($minX, $minY, $minZ);
			$this->pos2 = new Vector3($maxX, $maxY, $maxZ);

			if (!$this->piece && ($player = Server::getInstance()->getPlayer($this->player)) instanceof Player) {
				$this->close();
				$this->structure = new Vector3(floor(($this->pos2->getX() + $this->pos1->getX()) / 2), 0, floor(($this->pos2->getZ() + $this->pos1->getZ()) / 2));
				PacketUtils::sendFakeBlock($this->structure->floor(), $this->getWorld(), $player, BlockLegacyIds::STRUCTURE_BLOCK, CompoundTag::create()
					->setString("structureName", "selection")
					->setString("dataField", "")
					->setInt("xStructureOffset", $this->pos1->getFloorX() - $this->structure->getFloorX())
					->setInt("yStructureOffset", $this->pos1->getFloorY() - $this->structure->getFloorY())
					->setInt("zStructureOffset", $this->pos1->getFloorZ() - $this->structure->getFloorZ())
					->setInt("xStructureSize", $this->pos2->getFloorX() - $this->pos1->getFloorX() + 1)
					->setInt("yStructureSize", $this->pos2->getFloorY() - $this->pos1->getFloorY() + 1)
					->setInt("zStructureSize", $this->pos2->getFloorZ() - $this->pos1->getFloorZ() + 1)
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
					->setByte("isPowered", 0));
			}
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->structure);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->structure = $stream->getVector();
	}

	public function close(): void
	{
		if (!$this->piece && ($player = Server::getInstance()->getPlayerExact($this->player)) instanceof Player) {
			//Minecraft doesn't delete BlockData if the original Block shouldn't have some
			//this happens when whole Chunks get sent
			PacketUtils::sendFakeBlock($this->structure, $this->getWorld(), $player, BlockLegacyIds::STRUCTURE_BLOCK);
			PacketUtils::resendBlock($this->structure, $this->getWorld(), $player);
		}
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return Cube[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$min = VectorUtils::enforceHeight($this->pos1->add($offset));
		$max = VectorUtils::enforceHeight($this->pos2->add($offset));

		$pieces = [];
		for ($x = $min->getX() >> 4; $x <= $max->getX() >> 4; $x += 3) {
			for ($z = $min->getZ() >> 4; $z <= $max->getZ() >> 4; $z += 3) {
				$pieces[] = new Cube($this->getPlayer(), $this->getWorldName(), new Vector3(max(($x << 4) - $offset->getX(), $this->pos1->getX()), $this->pos1->getY(), max(($z << 4) - $offset->getZ(), $this->pos1->getZ())), new Vector3(min((($x + 2) << 4) + 15 - $offset->getX(), $this->pos2->getX()), $this->pos2->getY(), min((($z + 2) << 4) + 15 - $offset->getZ(), $this->pos2->getZ())), true);
			}
		}
		return $pieces;
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos1(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof self || $selection->getWorld() !== $player->getWorld()) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
		}

		$selection->setPos1($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos1", ["{x}" => (string) $position->getX(), "{y}" => (string) $position->getY(), "{z}" => (string) $position->getZ()]);
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos2(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof self || $selection->getWorld() !== $player->getWorld()) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
		}

		$selection->setPos2($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos2", ["{x}" => (string) $position->getX(), "{y}" => (string) $position->getY(), "{z}" => (string) $position->getZ()]);
	}
}