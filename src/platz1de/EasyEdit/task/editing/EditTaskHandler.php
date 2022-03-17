<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\utils\UpdateSubChunkBlocksInjector;
use platz1de\EasyEdit\world\InjectingSubChunkExplorer;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use UnexpectedValueException;

class EditTaskHandler
{
	private SafeSubChunkExplorer $origin; //Read-only
	private SafeSubChunkExplorer $result; //Write-only
	private BlockListSelection $changes;

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
	 */
	public function __construct(ReferencedChunkManager $origin, array $tiles, BlockListSelection $changes, bool $isFastSet)
	{
		//TODO: Never use changes as result (eg. copy)
		$this->origin = new SafeSubChunkExplorer($origin);
		if ($isFastSet) {
			$this->result = new InjectingSubChunkExplorer(clone $origin);
		} else {
			$this->result = new SafeSubChunkExplorer(clone $origin);
		}
		$this->changes = $changes;
		$this->originalTiles = $tiles;
		$this->tiles = array_map(static function (CompoundTag $tile): CompoundTag {
			return clone $tile;
		}, $tiles);
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
		return $this->changes->getBlockCount(); //hack to return copied blocks too
	}

	/**
	 * @return int
	 */
	public function getWrittenBlockCount(): int
	{
		return $this->origin->getWrittenBlockCount() + $this->result->getWrittenBlockCount() + $this->changes->getBlockCount();
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
	 * @return string[]
	 */
	public function prepareInjectionData(): array
	{
		if (!$this->result instanceof InjectingSubChunkExplorer) {
			throw new UnexpectedValueException("Handler wasn't caching for injection of result");
		}
		$injections = $this->result->getInjections();
		$return = [];
		foreach ($injections[0] as $hash => $injection) {
			World::getBlockXYZ($hash, $x, $y, $z);
			$return[$hash] = UpdateSubChunkBlocksInjector::getDataFrom($x, $y, $z, $injections[1][$hash], $injection->getBuffer());
		}
		return $return;
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
	 * @return int
	 */
	public function getResultingBlock(int $x, int $y, int $z): int
	{
		return $this->result->getBlockAt($x, $y, $z);
	}

	/**
	 * @param int  $x
	 * @param int  $y
	 * @param int  $z
	 * @param int  $block
	 * @param bool $overwrite
	 */
	public function changeBlock(int $x, int $y, int $z, int $block, bool $overwrite = true): void
	{
		$this->changes->addBlock($x, $y, $z, $this->origin->getBlockAt($x, $y, $z), $overwrite);

		//This currently blocks tiles being set before changing the block properly
		if (isset($this->tiles[World::blockHash($x, $y, $z)])) {
			$this->changes->addTile($this->originalTiles[World::blockHash($x, $y, $z)]);
			unset($this->tiles[World::blockHash($x, $y, $z)]);
			$this->affectedTiles++;
		}

		$this->result->setBlockAt($x, $y, $z, $block);
	}

	/**
	 * @param int  $x
	 * @param int  $y
	 * @param int  $z
	 * @param int  $ox x of block to be copied
	 * @param int  $oy y of block to be copied
	 * @param int  $oz z of block to be copied
	 * @param bool $overwrite
	 */
	public function copyBlock(int $x, int $y, int $z, int $ox, int $oy, int $oz, bool $overwrite = true): void
	{
		$this->changeBlock($x, $y, $z, $this->origin->getBlockAt($ox, $oy, $oz), $overwrite);

		//This currently blocks tiles being set before changing the block properly
		if (isset($this->tiles[World::blockHash($ox, $oy, $oz)])) {
			$this->addTile(TileUtils::offsetCompound($this->tiles[World::blockHash($ox, $oy, $oz)], $x - $ox, $y - $oy, $z - $oz));
			$this->affectedTiles++;
		}
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $ox
	 * @param int $oy
	 * @param int $oz
	 * @deprecated
	 */
	public function addToUndo(int $x, int $y, int $z, int $ox, int $oy, int $oz): void
	{
		$this->changes->addBlock($x + $ox, $y + $oy, $z + $oz, $this->origin->getBlockAt($x, $y, $z));

		if (isset($this->originalTiles[World::blockHash($x, $y, $z)])) {
			$this->changes->addTile(TileUtils::offsetCompound($this->originalTiles[World::blockHash($x, $y, $z)], $ox, $oy, $oz));
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
}