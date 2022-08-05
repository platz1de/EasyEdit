<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\world\blockupdate\InjectingData;
use platz1de\EasyEdit\world\blockupdate\InjectingSubChunkController;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use UnexpectedValueException;

class EditTaskHandler
{
	private ChunkController $origin; //Read-only
	private ChunkController $result; //Write-only
	private BlockListSelection $changes;

	/**
	 * @param ReferencedChunkManager $origin  Edited Chunks
	 * @param BlockListSelection     $changes Saves made changes, used for undoing
	 * @param bool                   $isFastSet
	 */
	public function __construct(ReferencedChunkManager $origin, BlockListSelection $changes, bool $isFastSet)
	{
		//TODO: Never use changes as result (eg. copy)
		$this->origin = new ChunkController($origin);
		if ($isFastSet) {
			$this->result = new InjectingSubChunkController(clone $origin);
		} else {
			$this->result = new ChunkController(clone $origin);
		}
		$this->changes = $changes;
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
	 * @return ReferencedChunkManager
	 */
	public function getResult(): ReferencedChunkManager
	{
		return $this->result->getManager();
	}

	/**
	 * @return string[]
	 */
	public function prepareAllInjectionData(): array
	{
		if (!$this->result instanceof InjectingSubChunkController) {
			throw new UnexpectedValueException("Handler wasn't caching for injection of result");
		}
		return array_map(static function (InjectingData $injection) {
			return $injection->toProtocol();
		}, $this->result->getInjections());
	}

	/**
	 * @param int $chunk
	 * @return string[]
	 */
	public function prepareInjectionData(int $chunk): array
	{
		if (!$this->result instanceof InjectingSubChunkController) {
			throw new UnexpectedValueException("Handler wasn't caching for injection of result");
		}
		return array_map(static function (InjectingData $injection) {
			return $injection->toProtocol();
		}, $this->result->getInjection($chunk));
	}

	/**
	 * @return BlockListSelection
	 */
	public function getChanges(): BlockListSelection
	{
		return $this->changes;
	}

	/**
	 * @return ChunkController
	 *
	 * @deprecated
	 */
	public function getOrigin(): ChunkController
	{
		return $this->origin;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getBlock(int $x, int $y, int $z): int
	{
		return $this->origin->getBlock($x, $y, $z);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getResultingBlock(int $x, int $y, int $z): int
	{
		return $this->result->getBlock($x, $y, $z);
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
		$this->changes->addBlock($x, $y, $z, $this->origin->getBlock($x, $y, $z), $overwrite);
		$this->changes->addTile($this->origin->getTile($x, $y, $z));

		$this->result->setBlock($x, $y, $z, $block);
		$this->result->setTile($x, $y, $z, null);
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
		$this->changeBlock($x, $y, $z, $this->origin->getBlock($ox, $oy, $oz), $overwrite);
		$this->addTile(TileUtils::offsetCompound($this->origin->getTile($ox, $oy, $oz), $x - $ox, $y - $oy, $z - $oz));
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
		$this->changes->addBlock($x + $ox, $y + $oy, $z + $oz, $this->origin->getBlock($x, $y, $z));
		$this->changes->addTile(TileUtils::offsetCompound($this->origin->getTile($x, $y, $z), $ox, $oy, $oz));
	}

	/**
	 * @param CompoundTag|null $tile
	 */
	public function addTile(?CompoundTag $tile): void
	{
		if ($tile !== null) {
			$this->result->setTile($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z), $tile);
		}
	}
}