<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\convert\ItemConvertor;
use pocketmine\block\tile\Container;
use pocketmine\nbt\tag\CompoundTag;
use UnexpectedValueException;

class InventoryConvertor extends TileConvertorPiece
{
	public static function toBedrock(CompoundTag $tile): void
	{
		$items = $tile->getListTag(Container::TAG_ITEMS);
		if ($items === null) {
			return;
		}
		foreach ($items as $item) {
			if (!$item instanceof CompoundTag) {
				throw new UnexpectedValueException("Items need to be represented as compound tags");
			}
			ItemConvertor::convertItemBedrock($item);
		}
	}

	public static function toJava(int $blockId, CompoundTag $tile): void
	{
		$items = $tile->getListTag(Container::TAG_ITEMS);
		if ($items === null) {
			return;
		}
		foreach ($items as $item) {
			if (!$item instanceof CompoundTag) {
				throw new UnexpectedValueException("Items need to be represented as compound tags");
			}
			ItemConvertor::convertItemJava($item);
		}
	}
}