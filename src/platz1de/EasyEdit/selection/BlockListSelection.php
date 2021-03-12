<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\tile\Tile;
use pocketmine\utils\Utils;
use RuntimeException;

abstract class BlockListSelection extends Selection
{
	/**
	 * @var ReferencedChunkManager
	 */
	private $manager;
	/**
	 * @var SubChunkIteratorManager
	 */
	private $iterator;
	/**
	 * @var CompoundTag[]
	 */
	private $tiles = [];

	/**
	 * BlockListSelection constructor.
	 * @param string  $player
	 * @param string  $level
	 * @param Vector3 $start
	 * @param int     $xSize
	 * @param int     $ySize
	 * @param int     $zSize
	 */
	public function __construct(string $player, string $level, Vector3 $start, int $xSize, int $ySize, int $zSize)
	{
		parent::__construct($player, $level, $start, new Vector3($start->getX() + $xSize, $start->getY() + $ySize, $start->getZ() + $zSize));
		$this->manager = new ReferencedChunkManager($level);
		$this->getManager()->load($start, $xSize, $zSize);
		$this->iterator = new SubChunkIteratorManager($this->manager);
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->manager;
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		for ($x = ($place->getX() + $this->pos1->getX()) >> 4; $x <= ($place->getX() + $this->pos2->getX()) >> 4; $x++) {
			for ($z = ($place->getZ() + $this->pos1->getZ()) >> 4; $z <= ($place->getZ() + $this->pos2->getZ()) >> 4; $z++) {
				$place->getLevelNonNull()->loadChunk($x, $z);
				$chunks[] = $place->getLevelNonNull()->getChunk($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 * @noinspection StaticClosureCanBeUsedInspection
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(function (int $x, int $y, int $z): void { }, $closure);
		for ($x = $place->getX() + $this->pos1->getX(); $x <= $place->getX() + $this->pos2->getX(); $x++) {
			for ($z = $place->getZ() + $this->pos1->getZ(); $z <= $place->getZ() + $this->pos2->getZ(); $z++) {
				for ($y = $place->getY() + $this->pos1->getY(); $y <= $place->getY() + $this->pos2->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @param int  $x
	 * @param int  $y
	 * @param int  $z
	 * @param int  $id
	 * @param int  $damage
	 * @param bool $overwrite
	 */
	public function addBlock(int $x, int $y, int $z, int $id, int $damage, bool $overwrite = true): void
	{
		if ($id === 0) {
			$id = 217; //structure_void
		}
		$this->iterator->moveTo($x, $y, $z);
		if ($overwrite || $this->iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) === 0){
			$this->iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id, $damage);
		}
	}

	/**
	 * @return SubChunkIteratorManager
	 */
	public function getIterator(): SubChunkIteratorManager
	{
		return $this->iterator;
	}

	/**
	 * @param CompoundTag $tile
	 */
	public function addTile(CompoundTag $tile): void
	{
		$this->tiles[Level::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
	}

	/**
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"chunks" => array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $this->getManager()->getChunks()),
			"level" => is_string($this->level) ? $this->level : $this->level->getName(),
			"minX" => $this->pos1->getX(),
			"minY" => $this->pos1->getY(),
			"minZ" => $this->pos1->getZ(),
			"maxX" => $this->pos2->getX(),
			"maxY" => $this->pos2->getY(),
			"maxZ" => $this->pos2->getZ(),
			"tiles" => $this->getTiles()
		]);
	}

	/**
	 * @param string $serialized
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function unserialize($serialized): void
	{
		$data = igbinary_unserialize($serialized);

		try {
			$this->level = Server::getInstance()->getLevelByName($data["level"]) ?? $data["level"];
		} catch (RuntimeException $exception) {
			$this->level = $data["level"];
		}

		$this->pos1 = new Vector3($data["minX"], $data["minY"], $data["minZ"]);
		$this->pos2 = new Vector3($data["maxX"], $data["maxY"], $data["maxZ"]);

		$this->player = $data["player"];
		$this->manager = new ReferencedChunkManager($data["level"]);
		foreach ($data["chunks"] as $chunk) {
			/** @var Chunk $chunk */
			$chunk = Chunk::fastDeserialize($chunk);
			$this->manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}
		$this->iterator = new SubChunkIteratorManager($this->manager);
		$this->tiles = $data["tiles"];
	}
}