<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SetCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/set", "Set the selected Area", [KnownPermissions::PERMISSION_EDIT], "//set <pattern>");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (($args[0] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		SetTask::queue(ArgumentParser::getSelection($player), ArgumentParser::parseCombinedPattern($player, $args, 0), $player->getPosition());
	}
}