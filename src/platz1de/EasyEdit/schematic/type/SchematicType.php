<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use pocketmine\nbt\tag\CompoundTag;

abstract class SchematicType
{
	public const TAG_WIDTH = "Width";
	public const TAG_HEIGHT = "Height";
	public const TAG_LENGTH = "Length";

	abstract public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void;
}