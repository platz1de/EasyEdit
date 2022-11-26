<?php

namespace platz1de\EasyEdit\selection;

use Generator;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

abstract class BlockListSelection extends Selection
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
	 * @param Vector3 $min
	 * @param Vector3 $max
	 * @return Generator<CompoundTag>
	 */
	public function getTiles(Vector3 $min, Vector3 $max): Generator
	{
		foreach ($this->tiles as $hash => $tile) {
			World::getBlockXYZ($hash, $x, $y, $z);
			if ($x >= $min->x && $y >= $min->y && $z >= $min->z && $x <= $max->x && $y <= $max->y && $z <= $max->z) {
				yield $tile;
			}
		}
	}

	abstract public function getBlockCount(): int;

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

	/**
	 * @param BlockListSelection $selection
	 */
	public function merge(BlockListSelection $selection): void
	{
		if ($selection->getPos1()->getY() < $this->getPos1()->getY()) {
			$this->setPos1($this->getPos1()->withComponents(null, $selection->getPos1()->getY(), null));
		}
		if ($selection->getPos2()->getY() > $this->getPos2()->getY()) {
			$this->setPos2($this->getPos2()->withComponents(null, $selection->getPos2()->getY(), null));
		}
		foreach ($selection->getTiles($selection->getPos1(), $selection->getPos2()) as $tile) {
			$this->addTile($tile);
		}
	}

	abstract public function createSafeClone(): BlockListSelection;

	public function containsData(): bool
	{
		return count($this->tiles) > 0;
	}
}