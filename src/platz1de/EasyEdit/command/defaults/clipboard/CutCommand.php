<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\editing\selection\CutTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class CutCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cut", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		CutTask::queue(ArgumentParser::getSelection($player), ArgumentParser::parseRelativePosition($player, $args[0] ?? null));
	}
}