<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SecondPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos2", [KnownPermissions::PERMISSION_SELECT], ["/2"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (count($args) > 2) {
			Cube::selectPos2($player, ArgumentParser::parseCoordinates($player, $args[0], $args[1], $args[2]));
		} else {
			Cube::selectPos2($player, $player->getPosition()->floor());
		}
	}
}