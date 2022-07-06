<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\convert\ItemConvertor;
use platz1de\EasyEdit\schematic\nbt\AbstractListTag;
use pocketmine\block\tile\Container;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use UnexpectedValueException;

class InventoryConvertor extends TileConvertorPiece
{
	public static function toBedrock(CompoundTag $tile): void
	{
		$items = $tile->getTag(Container::TAG_ITEMS);
		if (!$items instanceof AbstractListTag) {
			return;
		}
		$tile->setTag(Container::TAG_ITEMS, $new = new ListTag([], NBT::TAG_Compound));
		$count = $items->getLength();
		for ($i = 0; $i < $count; $i++) {
			$item = $items->next();
			if (!$item instanceof CompoundTag) {
				throw new UnexpectedValueException("Items need to be represented as compound tags");
			}
			$new->push($item);
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