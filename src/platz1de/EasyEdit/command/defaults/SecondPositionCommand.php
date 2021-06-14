<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\selection\Cube;
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
	 */
	public function process(Player $player, array $args): void
	{
		Cube::selectPos2($player, $player->floor());
	}
}