<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use pocketmine\item\VanillaItems;

class WandCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/wand", [KnownPermissions::PERMISSION_UTIL]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->asPlayer()->getInventory()->setItem($session->asPlayer()->getInventory()->getHeldItemIndex(), VanillaItems::WOODEN_AXE()); //some people prefer a command I guess
	}
}