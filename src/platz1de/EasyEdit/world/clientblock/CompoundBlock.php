<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Opaque;
use pocketmine\data\runtime\RuntimeDataReader;
use pocketmine\data\runtime\RuntimeDataWriter;
use pocketmine\nbt\tag\CompoundTag;

/**
 * A block carrying virtual tile data (for simplicity reasons)
 */
class CompoundBlock extends Opaque
{
	private int $typeLength;
	private int $type;
	private CompoundTag $data;

	public function __construct(int $typeLength, int $type, CompoundTag $staticData)
	{
		$this->data = $staticData;
		$this->typeLength = $typeLength;
		$this->type = $type;
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

	public function getRequiredTypeDataBits(): int
	{
		return $this->typeLength;
	}

	protected function describeType(RuntimeDataWriter|RuntimeDataReader $w): void
	{
		$w->int($this->typeLength, $this->type);
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}
}