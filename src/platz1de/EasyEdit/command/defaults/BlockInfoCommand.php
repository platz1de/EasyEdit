<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BlockInfoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/blockinfo", "Get a blockinfo stick", "easyedit.command.info", ["/bi"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$item = VanillaItems::STICK()->setNamedTag(CompoundTag::create()->setByte("isInfoStick", 1))->setCustomName(TextFormat::YELLOW . "InfoStick");
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [];
	}
}