<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\convert\ItemConvertor;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\block\tile\Container;
use pocketmine\nbt\tag\CompoundTag;
use Throwable;
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
			try {
				$javaId = $item->getString("id");
			} catch (Throwable) {
				continue; //probably already bedrock format, or at least not convertable
			}
			try {
				$i = ItemConvertor::convertItemBedrock($javaId);
			} catch (Throwable) {
				EditThread::getInstance()->debug("Couldn't convert item " . $javaId);
				continue;
			}
			$item->setShort("id", $i[0]);
			$item->setShort("Damage", $i[1]);
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
			try {
				$i = ItemConvertor::convertItemJava($item->getShort("id"), $item->getShort("Damage"));
			} catch (Throwable) {
				EditThread::getInstance()->debug("Couldn't convert item " . $item->getShort("id") . ":" . $item->getShort("Damage"));
				continue;
			}
			$item->removeTag("Damage");
			$item->setString("id", $i);
		}
	}
}