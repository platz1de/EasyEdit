<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;
use pocketmine\utils\Utils;

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
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param bool    $piece
	 */
	public function __construct(string $player, string $level, Vector3 $pos1, Vector3 $pos2, bool $piece = false)
	{
		parent::__construct($player, $level, $pos1, $pos2, $piece);
		$this->manager = new ReferencedChunkManager($level);
		$this->getManager()->load($pos1, $pos2);
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
				$chunks[] = LoaderManager::getChunk($place->getLevelNonNull(), $x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool
	{
		$start = $this->getCubicStart()->add($place);
		$end = $this->getCubicEnd()->add($place);

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
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
		$min = VectorUtils::enforceHeight($this->pos1->add($place));
		$max = VectorUtils::enforceHeight($this->pos2->add($place));
		for ($x = $min->getX(); $x <= $max->getX(); $x++) {
			for ($z = $min->getZ(); $z <= $max->getZ(); $z++) {
				for ($y = $min->getY(); $y <= $max->getY(); $y++) {
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
		if ($overwrite || $this->iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) === 0) {
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
	 * @return array
	 */
	public function getData(): array
	{
		return array_merge([
			"chunks" => array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $this->getManager()->getChunks()),
			"tiles" => $this->getTiles()
		], parent::getData());
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->manager = new ReferencedChunkManager($data["level"]);
		foreach ($data["chunks"] as $chunk) {
			$chunk = Chunk::fastDeserialize($chunk);
			$this->manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}
		$this->iterator = new SubChunkIteratorManager($this->manager);
		$this->tiles = $data["tiles"];
		parent::setData($data);
	}

	public function free(): void
	{
		$this->manager->cleanChunks();
		$this->tiles = [];
	}
}