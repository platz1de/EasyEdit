<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class CompoundTile extends Spawnable
{
	/**
	 * @var CompoundTag
	 */
	private $data;

	/**
	 * CompoundTile constructor.
	 * @param World       $world
	 * @param Vector3     $pos
	 * @param CompoundTag $data
	 */
	public function __construct(World $world, Vector3 $pos, CompoundTag $data)
	{
		$this->data = $data;
		parent::__construct($world, $pos);
	}

	/**
	 * @param CompoundTag $nbt
	 */
	protected function addAdditionalSpawnData(CompoundTag $nbt): void
	{
		foreach ($this->data->getValue() as $name => $tag) {
			$nbt->setTag($name, $tag);
		}
	}

	/**
	 * @param CompoundTag $nbt
	 */
	public function readSaveData(CompoundTag $nbt): void
	{
		throw new BadMethodCallException("CompoundTiles should never be created through TileFactory");
	}

	/**
	 * @param CompoundTag $nbt
	 */
	protected function writeSaveData(CompoundTag $nbt): void
	{
		throw new BadMethodCallException("CompoundTiles should never be saved");
	}
}