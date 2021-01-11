<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\EasyEdit;
use pocketmine\Player;

class FirstPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos1", "Set the first Position", "easyedit.position", "//pos1", ["/1"]);
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		EasyEdit::selectPos1($player, $player->floor());
	}
}