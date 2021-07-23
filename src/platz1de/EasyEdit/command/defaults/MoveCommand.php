<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\MoveTask;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MoveCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/move", "Move the selected area", "easyedit.command.paste", "//move <count>");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$amount = $args[0] ?? 1;

		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			/** @var Cube $selection */
			Selection::validate($selection, Cube::class);
		} catch (Exception $exception) {
			Messages::send($player, "no-selection");
			return;
		}

		MoveTask::queue(new MovingCube($selection->getPlayer(), $selection->getWorldName(), $selection->getPos1(), $selection->getPos2(), VectorUtils::moveVectorInSight($player->getLocation(), new Vector3(0, 0, 0), (int) $amount)), Position::fromObject($selection->getPos1(), $player->getWorld()));
	}
}