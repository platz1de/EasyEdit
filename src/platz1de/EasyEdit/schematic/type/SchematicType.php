<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\selection\BlockListSelection;
use pocketmine\nbt\tag\CompoundTag;

abstract class SchematicType
{
	abstract public static function readIntoSelection(CompoundTag $nbt, BlockListSelection $target): void;
}