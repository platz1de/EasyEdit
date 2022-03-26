<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class WandCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/wand", [KnownPermissions::PERMISSION_UTIL]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), VanillaItems::WOODEN_AXE()); //some people prefer a command I guess
	}
}