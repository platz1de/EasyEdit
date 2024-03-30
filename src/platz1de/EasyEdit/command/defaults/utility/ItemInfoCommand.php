<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Armor;
use pocketmine\item\Tool;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ImmutableTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class ItemInfoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/iteminfo", [KnownPermissions::PERMISSION_UTIL]);
	}

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

        $testNbt = CompoundTag::create()->setIntArray("test", [1, 2, 3, 4, 5]);

        $itemData = GlobalItemDataHandlers::getSerializer()->serializeType($item);
        $baseInfo = [
            // "{id}" => abs($item->getTypeId()),
            "{name}" => $item->getName(),
            // "{vanillaname}" => $item->getVanillaName(),
            "{id}" => $itemData->getName(),
            "{count}" => $item->getCount(),
            "{meta}" => $itemData->getMeta(),
            // "{nbt}" => $itemData->toNbt()->toString(),
            "{nbt}" => self::convertNbtToPrettyString($itemData->toNbt()),
            // "{nbt}" => self::convertNbtToPrettyString($testNbt),
            "{java_nbt}" => $itemData->toNbt()->toString()
        ];

        return $baseInfo;
    }

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
        $itemInHand = $session->asPlayer()->getInventory()->getItemInHand();
        $session->sendMessage("item-info", self::createItemInfo($session, $itemInHand));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from [];
	}
}