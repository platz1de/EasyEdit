<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Opaque;
use pocketmine\data\runtime\InvalidSerializedRuntimeDataException;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\nbt\tag\CompoundTag;

/**
 * A block carrying virtual tile data (for simplicity reasons)
 */
class CompoundBlock extends Opaque
{
	/**
	 * @param int         $typeLength
	 * @param int         $type
	 * @param CompoundTag $data
	 */
	public function __construct(private int $typeLength, private int $type, private CompoundTag $data)
	{
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

	protected function describeType(RuntimeDataDescriber $w): void
	{
		$w->int((int) ceil(log($this->typeLength, 2)), $this->type);
		if ($this->type > $this->typeLength) {
			throw new InvalidSerializedRuntimeDataException("Type $this->type is too big for type length $this->typeLength");
		}
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}
}