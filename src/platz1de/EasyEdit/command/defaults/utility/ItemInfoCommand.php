<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\session\Session;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Armor;
use pocketmine\item\Tool;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class ItemInfoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/iteminfo", [KnownPermissions::PERMISSION_UTIL]);
	}

    public static function getInternalNameForItem(Item $item): ?string
    {
        // could consider vanilla name assumption (to lowercase and replace spaces with underscores) -> names with `'` are definitely different

        foreach(VanillaItems::getAll() as $internalName => $vanillaItem) {
            if ($vanillaItem->getTypeId() === abs($item->getTypeId())) {
                return $internalName;
            }
        }
        
        foreach(VanillaBlocks::getAll() as $internalBlockName => $vanillaBlock) {
            if ($vanillaBlock->getTypeId() === abs($item->getTypeId())) {
                return $internalBlockName;
            }
        }
        return null;
    }

    public static function createItemInfo(Session $session, Item $item): array
    {
        $baseInfo = [
            "{id}" => $item->getTypeId(),
            "{name}" => $item->getName(),
            "{vanillaname}" => $item->getVanillaName(),
            "{internalname}" => self::getInternalNameForItem($item) ?? "N/A",
            "{count}" => $item->getCount(),
            "{damage}" => "N/A",
            "{durability}" => "N/A",
            "{defense}" => "N/A"
        ];
        
        if ($item instanceof Tool) {
            $baseInfo["{damage}"] = $item->getDamage();
            $baseInfo["{durability}"] = $item->getMaxDurability();
        }

        if ($item instanceof Armor) {
            $baseInfo["{defense}"] = $item->getDefensePoints();
        }

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