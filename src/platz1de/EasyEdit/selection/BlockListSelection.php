<?php

namespace platz1de\EasyEdit\selection;

use Generator;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

abstract class BlockListSelection extends Selection implements SelectionIdentifier
{
	/**
	 * @var CompoundTag[]
	 */
	private array $tiles = [];

	/**
	 * @param int  $x
	 * @param int  $y
	 * @param int  $z
	 * @param int  $id
	 * @param bool $overwrite
	 */
	abstract public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void;

	/**
	 * @param CompoundTag|null $tile
	 */
	public function addTile(?CompoundTag $tile): void
	{
		if ($tile !== null) {
			$this->tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}
	}

	/**
	 * @param BlockVector $min
	 * @param BlockVector $max
	 * @return Generator<CompoundTag>
	 */
	public function getTiles(BlockVector $min, BlockVector $max): Generator
	{
		foreach ($this->tiles as $hash => $tile) {
			World::getBlockXYZ($hash, $x, $y, $z);
			if ($x >= $min->x && $y >= $min->y && $z >= $min->z && $x <= $max->x && $y <= $max->y && $z <= $max->z) {
				yield $tile;
			}
		}
	}

	abstract public function getBlockCount(): int;

	public function toIdentifier(): StoredSelectionIdentifier
	{
		return StorageModule::store($this);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putCompounds($this->tiles);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->tiles = $stream->getCompounds();
	}

	public function free(): void
	{
		$this->tiles = [];
	}

	abstract public function createSafeClone(): BlockListSelection;

	public function containsData(): bool
	{
		return count($this->tiles) > 0;
	}
}