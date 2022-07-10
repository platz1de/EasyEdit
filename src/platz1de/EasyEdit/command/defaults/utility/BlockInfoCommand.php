<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class BlockInfoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/blockinfo", [KnownPermissions::PERMISSION_UTIL], ["/bi"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$item = VanillaItems::STICK()->setNamedTag(CompoundTag::create()->setByte("isInfoStick", 1))->setCustomName(TextFormat::YELLOW . "InfoStick");
		$session->asPlayer()->getInventory()->setItem($session->asPlayer()->getInventory()->getHeldItemIndex(), $item);
	}
}