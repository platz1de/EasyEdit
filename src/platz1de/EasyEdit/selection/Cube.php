<?php

namespace platz1de\EasyEdit\selection;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\cubic\CubicIterator;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Tile;
use pocketmine\utils\AssumptionFailedError;
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
		$this->structure = new Vector3();

		parent::__construct($player, $level, $pos1, $pos2, $piece);
	}

	public function update(): void
	{
		if ($this->isValid()) {
			$minX = min($this->pos1->getX(), $this->pos2->getX());
			$maxX = max($this->pos1->getX(), $this->pos2->getX());
			$minY = max(min($this->pos1->getY(), $this->pos2->getY()), 0);
			$maxY = min(max($this->pos1->getY(), $this->pos2->getY()), Level::Y_MASK);
			$minZ = min($this->pos1->getZ(), $this->pos2->getZ());
			$maxZ = max($this->pos1->getZ(), $this->pos2->getZ());

			$this->pos1->setComponents($minX, $minY, $minZ);
			$this->pos2->setComponents($maxX, $maxY, $maxZ);

			if (!$this->piece && ($player = Server::getInstance()->getPlayer($this->player)) instanceof Player) {
				$this->close();
				$this->structure = new Vector3(floor(($this->pos2->getX() + $this->pos1->getX()) / 2), 0, floor(($this->pos2->getZ() + $this->pos1->getZ()) / 2));
				$this->level->sendBlocks([$player], [BlockFactory::get(BlockIds::STRUCTURE_BLOCK, 0, new Position($this->structure->getFloorX(), $this->structure->getFloorY(), $this->structure->getFloorZ(), $this->level))]);
				$nbt = new CompoundTag("", [
					new StringTag(Tile::TAG_ID, "StructureBlock"),
					new IntTag(Tile::TAG_X, $this->structure->getFloorX()),
					new IntTag(Tile::TAG_Y, $this->structure->getFloorY()),
					new IntTag(Tile::TAG_Z, $this->structure->getFloorZ()),
					new StringTag("structureName", "selection"),
					new StringTag("dataField", ""),
					new IntTag("xStructureOffset", $this->pos1->getFloorX() - $this->structure->getFloorX()),
					new IntTag("yStructureOffset", $this->pos1->getFloorY() - $this->structure->getFloorY()),
					new IntTag("zStructureOffset", $this->pos1->getFloorZ() - $this->structure->getFloorZ()),
					new IntTag("xStructureSize", $this->pos2->getFloorX() - $this->pos1->getFloorX() + 1),
					new IntTag("yStructureSize", $this->pos2->getFloorY() - $this->pos1->getFloorY() + 1),
					new IntTag("zStructureSize", $this->pos2->getFloorZ() - $this->pos1->getFloorZ() + 1),
					new IntTag("data", 5),
					new ByteTag("rotation", 0),
					new ByteTag("mirror", 0),
					new FloatTag("integrity", 100.0),
					new LongTag("seed", 0),
					new ByteTag("ignoreEntities", 1),
					new ByteTag("includePlayers", 0),
					new ByteTag("removeBlocks", 0),
					new ByteTag("showBoundingBox", 1),
					new ByteTag("isMovable", 1),
					new ByteTag("isPowered", 0)
				]);

				$nbtWriter = new NetworkLittleEndianNBTStream();
				$spawnData = $nbtWriter->write($nbt);
				if ($spawnData === false) {
					throw new AssumptionFailedError("NBTStream->write() should not return false when given a CompoundTag");
				}

				$pk = new BlockActorDataPacket();
				$pk->x = $this->structure->getFloorX();
				$pk->y = $this->structure->getFloorY();
				$pk->z = $this->structure->getFloorZ();
				$pk->namedtag = $spawnData;

				$player->dataPacket($pk);
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
			$this->level->sendBlocks([$player], [BlockFactory::get(BlockIds::STRUCTURE_BLOCK, 0, new Position($this->structure->getFloorX(), $this->structure->getFloorY(), $this->structure->getFloorZ(), $this->level))]);
			$this->level->sendBlocks([$player], [$this->level->getBlock($this->structure->floor())]);
		}
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @return array
	 */
	public function split(): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$level = $this->getLevel();
		if ($level instanceof Level) {
			$level = $level->getFolderName();
		}
		$pieces = [];
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x += 3) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z += 3) {
				$pieces[] = new Cube($this->getPlayer(), $level, new Vector3(max($x << 4, $this->pos1->getX()), $this->pos1->getY(), max($z << 4, $this->pos1->getZ())), new Vector3(min((($x + 2) << 4) + 15, $this->pos2->getX()), $this->pos2->getY(), min((($z + 2) << 4) + 15, $this->pos2->getZ())), true);
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
			if (!$selection instanceof self || $selection->getLevel() !== $player->getLevel()) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getLevelNonNull()->getFolderName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getLevelNonNull()->getFolderName());
		}

		$selection->setPos1($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos1");
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos2(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof self || $selection->getLevel() !== $player->getLevel()) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getLevelNonNull()->getFolderName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getLevelNonNull()->getFolderName());
		}

		$selection->setPos2($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos2");
	}
}