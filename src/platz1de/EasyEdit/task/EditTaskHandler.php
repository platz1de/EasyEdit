<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

class EditTaskHandler
{
	private SafeSubChunkExplorer $origin; //Read-only
	private SafeSubChunkExplorer $result; //Write-only
	private BlockListSelection $changes;
	private Pattern $pattern;
	private SelectionContext $selectionContext;

	/**
	 * @var CompoundTag[]
	 */
	private array $originalTiles;
	/**
	 * @var CompoundTag[]
	 */
	private array $tiles;

	private int $affectedTiles = 0;

	/**
	 * @param ReferencedChunkManager $origin  Edited Chunks
	 * @param CompoundTag[]          $tiles   CompoundTags of tiles in edited area
	 * @param BlockListSelection     $changes Saves made changes, used for undoing
	 * @param AdditionalDataManager  $data
	 * @param Pattern                $pattern
	 * @param SelectionContext       $selectionContext
	 */
	public function __construct(ReferencedChunkManager $origin, array $tiles, BlockListSelection $changes, AdditionalDataManager $data, Pattern $pattern, SelectionContext $selectionContext)
	{
		//TODO: Never use changes as result (eg. copy)
		$this->origin = new SafeSubChunkExplorer($origin);
		$this->result = new SafeSubChunkExplorer(clone $origin);
		$this->changes = $changes;
		$this->originalTiles = $tiles;
		$this->tiles = array_map(static function (CompoundTag $tile): CompoundTag {
			return clone $tile;
		}, $tiles);
		$this->pattern = $pattern;
		$this->selectionContext = $selectionContext;
		//TODO: parse data
	}

	/**
	 * @param string                $world
	 * @param string                $chunkData
	 * @param string                $tileData
	 * @param BlockListSelection    $undo
	 * @param AdditionalDataManager $data
	 * @param Pattern               $pattern
	 * @return EditTaskHandler
	 */
	public static function fromData(string $world, string $chunkData, string $tileData, BlockListSelection $undo, AdditionalDataManager $data, Pattern $pattern): EditTaskHandler
	{
		$origin = new ReferencedChunkManager($world);
		$chunks = new ExtendedBinaryStream($chunkData);
		while (!$chunks->feof()) {
			$origin->setChunk($chunks->getInt(), $chunks->getInt(), FastChunkSerializer::deserializeTerrain($chunks->getString()));
		}

		$tiles = new ExtendedBinaryStream($tileData);
		$tileList = [];
		while (!$tiles->feof()) {
			$tile = $tiles->getCompound();
			$tileList[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}

		return new EditTaskHandler($origin, $tileList, $undo, $data, $pattern, $pattern->getSelectionContext());
	}

	/**
	 * @return int
	 */
	public function getChunkCount(): int
	{
		return count($this->origin->getManager()->getChunks());
	}

	/**
	 * @return int
	 */
	public function getChangedBlockCount(): int
	{
		return $this->changes->getIterator()->getWrittenBlockCount(); //hack to return copied blocks too
	}

	/**
	 * @return int
	 */
	public function getWrittenBlockCount(): int
	{
		return $this->origin->getWrittenBlockCount() + $this->result->getWrittenBlockCount() + $this->changes->getIterator()->getWrittenBlockCount();
	}

	/**
	 * @return int
	 */
	public function getReadBlockCount(): int
	{
		//TODO: Blocklist selections
		return $this->origin->getReadBlockCount() + $this->result->getReadBlockCount() + $this->result->getReadBlockCount();
	}

	/**
	 * @return int
	 */
	public function getChangedTileCount(): int
	{
		return $this->affectedTiles;
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getResult(): ReferencedChunkManager
	{
		return $this->result->getManager();
	}

	/**
	 * @return BlockListSelection
	 */
	public function getChanges(): BlockListSelection
	{
		return $this->changes;
	}

	/**
	 * @return SafeSubChunkExplorer
	 *
	 * @deprecated
	 */
	public function getOrigin(): SafeSubChunkExplorer
	{
		return $this->origin;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getBlock(int $x, int $y, int $z): int
	{
		return $this->origin->getBlockAt($x, $y, $z);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $block
	 */
	public function changeBlock(int $x, int $y, int $z, int $block): void
	{
		$this->changes->addBlock($x, $y, $z, $this->getBlock($x, $y, $z));

		//This currently blocks tiles being set before changing the block properly
		if (isset($this->tiles[World::blockHash($x, $y, $z)])) {
			$this->changes->addTile($this->originalTiles[World::blockHash($x, $y, $z)]);
			unset($this->tiles[World::blockHash($x, $y, $z)]);
			$this->affectedTiles++;
		}

		$this->result->setBlockAt($x, $y, $z, $block);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $ox x of block to be copied
	 * @param int $oy y of block to be copied
	 * @param int $oz z of block to be copied
	 */
	public function copyBlock(int $x, int $y, int $z, int $ox, int $oy, int $oz): void
	{
		$this->changeBlock($x, $y, $z, $this->getBlock($ox, $oy, $oz));

		//This currently blocks tiles being set before changing the block properly
		if (isset($this->tiles[World::blockHash($ox, $oy, $oz)])) {
			$this->addTile(TileUtils::offsetCompound($this->tiles[World::blockHash($ox, $oy, $oz)], new Vector3($x - $ox, $y - $oy, $z - $oz)));
			$this->affectedTiles++;
		}
	}

	/**
	 * @param int     $x
	 * @param int     $y
	 * @param int     $z
	 * @param Vector3 $offset
	 *
	 * @deprecated
	 */
	public function addToUndo(int $x, int $y, int $z, Vector3 $offset): void
	{
		$this->changes->addBlock($x + $offset->getFloorX(), $y + $offset->getFloorY(), $z + $offset->getFloorZ(), $this->getBlock($x, $y, $z));

		if (isset($this->originalTiles[World::blockHash($x, $y, $z)])) {
			$this->changes->addTile(TileUtils::offsetCompound($this->originalTiles[World::blockHash($x, $y, $z)], $offset));
			$this->affectedTiles++;
		}
	}

	/**
	 * @param CompoundTag $tile
	 */
	public function addTile(CompoundTag $tile): void
	{
		$this->tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		$this->affectedTiles++;
	}

	/**
	 * @return Pattern
	 */
	public function getPattern(): Pattern
	{
		return $this->pattern;
	}

	/**
	 * @return SelectionContext
	 */
	public function getSelectionContext(): SelectionContext
	{
		return $this->selectionContext;
	}
}