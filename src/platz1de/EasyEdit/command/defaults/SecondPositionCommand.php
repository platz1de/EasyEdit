<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\selection\Cube;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SecondPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos2", "Set the second Position", "easyedit.position", "//pos2 [x] [y] [z]", ["/2"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if(count($args) > 2){
			Cube::selectPos2($player, new Vector3((int) $args[0], (int) $args[1], (int) $args[2]));
		}else{
			Cube::selectPos2($player, $player->floor());
		}
	}
}