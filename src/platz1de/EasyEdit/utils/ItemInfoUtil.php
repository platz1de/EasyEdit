<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\convert\ItemConvertor;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ImmutableTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class ItemInfoUtil
{
	public static function convertNbtToPrettyString(CompoundTag|ListTag $nbt): string
	{
		$isCompoundTag = $nbt instanceof CompoundTag;
		$openingChar = $isCompoundTag ? "{" : "[";
		$stringified = $openingChar;

		$idx = 0;
		foreach ($nbt->getValue() as $name => $tag) {
			if ($tag instanceof CompoundTag || $tag instanceof ListTag) {
				$value = self::convertNbtToPrettyString($tag);
			} else {
				$tagAsString = $tag->toString();
				$value = substr($tagAsString, strpos($tagAsString, "=") + 1);
			}

			$valueColor = TextFormat::RESET;
			if ($tag instanceof StringTag) {
				$valueColor = TextFormat::GREEN;
			} else if (!($tag instanceof IntArrayTag || $tag instanceof ByteArrayTag)
				&& $tag instanceof ImmutableTag) {
				$valueColor = TextFormat::GOLD;
			}

			$hasNextTagChar = ($idx < count($nbt->getValue()) - 1) ? ", " : "";
			$lhs = $isCompoundTag ? TextFormat::AQUA . $name . TextFormat::RESET . ": " : "";
			$stringified .= $lhs . $valueColor . $value . TextFormat::RESET . $hasNextTagChar;

			$idx++;
		}

		$closingChar = $isCompoundTag ? "}" : "]";
		return $stringified . $closingChar;
	}

	public static function createItemInfo(Item $item): array
	{
		$itemData = GlobalItemDataHandlers::getSerializer()->serializeType($item);
		$javaNbt = ItemConvertor::convertItemJava($itemData->toNbt());
		return [
			"{name}" => $item->getName(),
			"{id}" => $itemData->getName(),
			"{count}" => $item->getCount(),
			"{meta}" => $itemData->getMeta(),
			"{nbt}" => self::convertNbtToPrettyString($itemData->toNbt()),
			"{java_nbt}" => $javaNbt === null ? "-" : self::convertNbtToPrettyString($javaNbt)
		];
	}
}