<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\convert\ItemConvertor;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ImmutableTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class ItemInfoUtil
{

    public static function convertNbtToPrettyString(CompoundTag $nbt): string
    {
        $stringified = "{";

        $idx = 0;
		foreach($nbt->getValue() as $name => $tag) {
            $value = null;
            if ($tag instanceof CompoundTag) {
                $value = self::convertNbtToPrettyString($tag);
            } else {
                $tagAsString = $tag->toString();
                $value = substr($tagAsString, strpos($tagAsString,"=") + 1);
            }

            $valueColor = "§r";
            if ($tag instanceof StringTag) {
                $valueColor = "§a";
            } else if (!($tag instanceof IntArrayTag || $tag instanceof ByteArrayTag) 
                && $tag instanceof ImmutableTag) 
            {
                $valueColor = "§6";
            }
            
            $hasNextTagChar = ($idx < count($nbt->getValue())-1) ? ", " : "";
            $stringified .= "§b" . $name . "§r: " . $valueColor . $value . "§r" . $hasNextTagChar;

            $idx++;
        }

        return $stringified . "}";
    }

    public static function createItemInfo(Session $session, Item $item): array
    {
        $itemData = GlobalItemDataHandlers::getSerializer()->serializeType($item);
        $baseInfo = [
            "{name}" => $item->getName(),
            "{id}" => $itemData->getName(),
            "{count}" => $item->getCount(),
            "{meta}" => $itemData->getMeta(),
            "{nbt}" => self::convertNbtToPrettyString($itemData->toNbt()),
            "{java_nbt}" => self::convertNbtToPrettyString(ItemConvertor::convertItemJava($itemData->toNbt()))
        ];

        return $baseInfo;
    }
}