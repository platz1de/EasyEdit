<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Opaque;
use pocketmine\nbt\tag\CompoundTag;

/**
 * A block carrying virtual tile data (for simplicity reasons)
 */
class CompoundBlock extends Opaque
{
	private CompoundTag $data;

	public function __construct(CompoundTag $staticData)
	{
		$this->data = $staticData;
		parent::__construct(new BlockIdentifier($id = BlockTypeIds::newId()), "EasyEdit Helper $id", new BlockTypeInfo(BlockBreakInfo::instant()));
	}

	public function getData(): CompoundTag
	{
		return $this->data;
	}

	public function __clone()
	{
		$this->data = clone $this->data;
		parent::__clone();
	}
}