<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SphereCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/sphere", "Set a sphere", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], "//sphere <radius> <pattern>", ["/sph"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 2, $this);
		SetTask::queue(Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (int) $args[0]), ArgumentParser::parseCombinedPattern($player, $args, 1), $player->getPosition());
	}
}