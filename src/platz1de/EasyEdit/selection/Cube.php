<?php

namespace platz1de\EasyEdit\selection;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\format\Chunk;
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
use RuntimeException;

class Cube extends Selection
{
	/**
	 * @var Level|string
	 */
	private $level;

	/**
	 * @var Vector3
	 */
	private $pos1;
	/**
	 * @var Vector3
	 */
	private $pos2;

	/**
	 * @var Vector3
	 */
	private $selected1;
	/**
	 * @var Vector3
	 */
	private $selected2;
	/**
	 * @var Vector3
	 */
	private $structure;

	/**
	 * Cube constructor.
	 * @param string       $player
	 * @param Level        $level
	 * @param null|Vector3 $pos1
	 * @param null|Vector3 $pos2
	 */
	public function __construct(string $player, Level $level, ?Vector3 $pos1 = null, ?Vector3 $pos2 = null)
	{
		parent::__construct($player);

		$this->level = $level;

		$this->update();

		if ($pos1 !== null) {
			$this->pos1 = clone($this->selected1 = $pos1);
		}
		if ($pos2 !== null) {
			$this->pos2 = clone($this->selected2 = $pos2);
		}
	}

	/**
	 * @return Vector3[]
	 */
	public function getAffectedBlocks(): array
	{
		$blocks = [];
		for ($x = $this->pos1->getX(); $x <= $this->pos2->getX(); $x++) {
			for ($y = $this->pos1->getY(); $y <= $this->pos2->getY(); $y++) {
				for ($z = $this->pos1->getZ(); $z <= $this->pos2->getZ(); $z++) {
					$blocks[] = new Vector3($x, $y, $z);
				}
			}
		}
		return $blocks;
	}

	public function update(): void
	{
		if ($this->pos1 instanceof Vector3 && $this->pos2 instanceof Vector3) {
			$minX = min($this->pos1->getX(), $this->pos2->getX());
			$maxX = max($this->pos1->getX(), $this->pos2->getX());
			$minY = min($this->pos1->getY(), $this->pos2->getY());
			$maxY = max($this->pos1->getY(), $this->pos2->getY());
			$minZ = min($this->pos1->getZ(), $this->pos2->getZ());
			$maxZ = max($this->pos1->getZ(), $this->pos2->getZ());

			$this->pos1->setComponents($minX, $minY, $minZ);
			$this->pos2 = $this->pos2->setComponents($maxX, $maxY, $maxZ);

			if (($player = Server::getInstance()->getPlayer($this->player)) instanceof Player) {
				if(isset($this->structure)){
					$this->level->sendBlocks([$player], [$this->level->getBlock($this->structure)]);
				}
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
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x++) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z++) {
				$this->getLevel()->loadChunk($x, $z);
				$chunks[] = $this->getLevel()->getChunk($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"level" => is_string($this->level) ? $this->level : $this->level->getName(),
			"minX" => $this->pos1->getX(),
			"minY" => $this->pos1->getY(),
			"minZ" => $this->pos1->getZ(),
			"maxX" => $this->pos2->getX(),
			"maxY" => $this->pos2->getY(),
			"maxZ" => $this->pos2->getZ()
		]);
	}

	public function unserialize($serialized): void
	{
		$data = igbinary_unserialize($serialized);
		$this->player = $data["player"];
		try {
			$this->level = Server::getInstance()->getLevelByName($data["level"]);
		} catch (RuntimeException $exception) {
			$this->level = $data["level"];
		}
		$this->pos1 = new Vector3($data["minX"], $data["minY"], $data["minZ"]);
		$this->pos2 = new Vector3($data["maxX"], $data["maxY"], $data["maxZ"]);
	}

	/**
	 * @param Vector3 $pos1
	 */
	public function setPos1(Vector3 $pos1): void
	{
		$this->pos1 = clone($this->selected1 = $pos1);
		if ($this->selected2 !== null) {
			$this->pos2 = clone($this->selected2);
		}

		$this->update();
	}

	/**
	 * @param Vector3 $pos2
	 */
	public function setPos2(Vector3 $pos2): void
	{
		if ($this->selected1 !== null) {
			$this->pos1 = clone($this->selected1);
		}
		$this->pos2 = clone($this->selected2 = $pos2);

		$this->update();
	}

	/**
	 * @return Level|string
	 */
	private function getLevel()
	{
		return $this->level;
	}

	public function close(): void
	{
		if(isset($this->structure) and ($player = Server::getInstance()->getPlayerExact($this->player)) instanceof Player){
			$this->level->sendBlocks([$player], [$this->level->getBlock($this->structure)]);
		}
	}
}