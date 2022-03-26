<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\editing\selection\CountTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class CountCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/count", [KnownPermissions::PERMISSION_SELECT]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (isset($args[0])) {
			$selection = Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (float) $args[0]);
		} else {
			$selection = ArgumentParser::getSelection($player);
		}

		CountTask::queue($selection, $player->getPosition());
	}
}