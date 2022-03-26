<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\editing\selection\SmoothTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SmoothCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/smooth", [KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		SmoothTask::queue(ArgumentParser::getSelection($player), $player->getPosition());
	}
}