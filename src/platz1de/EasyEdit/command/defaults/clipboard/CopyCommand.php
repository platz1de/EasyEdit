<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\editing\selection\CopyTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class CopyCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/copy", [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		CopyTask::queue(ArgumentParser::getSelection($player), ArgumentParser::parseRelativePosition($player, $args[0] ?? null));
	}
}