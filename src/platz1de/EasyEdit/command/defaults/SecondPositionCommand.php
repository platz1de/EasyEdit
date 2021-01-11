<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\EasyEdit;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SecondPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos2", "Set the second Position", "easyedit.position", "//pos2", ["/2"]);
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		EasyEdit::selectPos2($player, $player->floor());
	}
}