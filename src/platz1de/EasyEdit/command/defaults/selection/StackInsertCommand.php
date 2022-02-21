<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\StackedCube;
use platz1de\EasyEdit\task\editing\selection\StackTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class StackInsertCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/istack", "Stack the selected area without overwriting existing terrain", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], "//istack <count>");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$count = (int) ($args[0] ?? 1);
		$selection = ArgumentParser::getCube($player);

		StackTask::queue(new StackedCube($selection->getPlayer(), $selection->getWorldName(), $selection->getPos1(), $selection->getPos2(), VectorUtils::moveVectorInSight($player->getLocation(), Vector3::zero(), $count)), $player->getPosition(), true);
	}
}