<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\editing\PasteBlockStatesTask;
use pocketmine\player\Player;

class PasteStatesCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pastestates", "Paste all known blockstates (debug command)", [KnownPermissions::PERMISSION_MANAGE, KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT]);
	}

	public function process(Player $player, array $args): void
	{
		PasteBlockStatesTask::queue($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition());
	}
}