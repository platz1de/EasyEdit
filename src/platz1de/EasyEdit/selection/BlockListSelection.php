<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\tile\Tile;
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
	 * @param CompoundTag $tile
	 */
	public function addTile(CompoundTag $tile): void
	{
		$this->tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
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
		foreach ($selection->getTiles() as $tile) {
			$this->addTile($tile);
		}
	}

	public function checkCachedData(): void { }
}