<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class ExtendCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/extend", "Extend the selected Area", "easyedit.position", "//extend [count|vertical]", ["/expand"]);
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		$count = $args[0] ?? 1;

		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			/** @var Cube $selection */
			Selection::validate($selection, Cube::class);
		} catch (Exception $exception) {
			Messages::send($player, "no-selection");
			return;
		}

		$pos1 = $selection->getPos1();
		$pos2 = $selection->getPos2();

		if ($count === "vert" || $count === "vertical") {
			$pos1 = new Vector3($pos1->getX(), 0, $pos1->getZ());
			$pos2 = new Vector3($pos2->getX(), Level::Y_MASK, $pos2->getZ());
		} elseif ($player->getPitch() >= 45) {
			$pos1 = $pos1->getSide(Vector3::SIDE_DOWN, (int) $count);
		} elseif ($player->getPitch() <= -45) {
			$pos2 = $pos2->getSide(Vector3::SIDE_UP, (int) $count);
		} elseif ($player->getYaw() >= 315 || $player->getYaw() < 45) {
			$pos2 = $pos2->getSide(Vector3::SIDE_SOUTH, (int) $count);
		} elseif ($player->getYaw() >= 45 && $player->getYaw() < 135) {
			$pos1 = $pos1->getSide(Vector3::SIDE_WEST, (int) $count);
		} elseif ($player->getYaw() >= 135 && $player->getYaw() < 225) {
			$pos1 = $pos1->getSide(Vector3::SIDE_NORTH, (int) $count);
		} else {
			$pos2 = $pos2->getSide(Vector3::SIDE_EAST, (int) $count);
		}

		$selection->setPos1($pos1);
		$selection->setPos2($pos2);
	}
}