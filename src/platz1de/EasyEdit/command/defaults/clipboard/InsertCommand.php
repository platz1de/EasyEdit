<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\DynamicStoredPasteTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class InsertCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/insert", [KnownPermissions::PERMISSION_CLIPBOARD, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		DynamicStoredPasteTask::queue($player->getName(), ArgumentParser::getClipboard($player), $player->getPosition(), true, true);
	}
}