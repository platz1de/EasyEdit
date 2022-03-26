<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\task\editing\selection\MoveTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MoveCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/move", [KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$selection = ArgumentParser::getCube($player);

		MoveTask::queue(new MovingCube($selection->getPlayer(), $selection->getWorldName(), $selection->getPos1(), $selection->getPos2(), ArgumentParser::parseDirectionVector($player, $args[0] ?? null, $args[1] ?? null)), Position::fromObject($selection->getPos1(), $player->getWorld()));
	}
}