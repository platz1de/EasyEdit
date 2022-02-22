<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\selection\CenterPattern;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class CenterCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/center", "Set the center Blocks (1-8)", [KnownPermissions::PERMISSION_EDIT], "//center [block]", ["/middle"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		SetTask::queue(ArgumentParser::getSelection($player), CenterPattern::from([ArgumentParser::parseCombinedPattern($player, $args, 0, "stone")]), $player->getPosition());
	}
}