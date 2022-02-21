<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\task\editing\selection\MoveTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MoveCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/move", "Move the selected area", [KnownPermissions::PERMISSION_EDIT], "//move <count>");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$amount = (int) ($args[0] ?? 1);
		$selection = ArgumentParser::getCube($player);

		MoveTask::queue(new MovingCube($selection->getPlayer(), $selection->getWorldName(), $selection->getPos1(), $selection->getPos2(), VectorUtils::moveVectorInSight($player->getLocation(), Vector3::zero(), $amount)), Position::fromObject($selection->getPos1(), $player->getWorld()));
	}
}