<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SidesCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/sides", "Set sides of the selected area", [KnownPermissions::PERMISSION_EDIT], "//sides [pattern]", ["/side"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		SetTask::queue(ArgumentParser::getSelection($player), SidesPattern::from([ArgumentParser::parseCombinedPattern($player, $args, 0, "stone")]), $player->getPosition());
	}
}