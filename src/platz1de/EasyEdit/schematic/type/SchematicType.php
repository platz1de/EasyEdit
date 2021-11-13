<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use pocketmine\nbt\tag\CompoundTag;

abstract class SchematicType
{
	abstract public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void;
}