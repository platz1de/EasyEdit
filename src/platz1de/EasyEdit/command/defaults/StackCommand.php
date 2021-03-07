<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\BlockPattern;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\CopyTask;
use platz1de\EasyEdit\task\selection\PasteTask;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

class StackCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/stack", "Stack the selected area", "easyedit.command.paste", "//stack <count>");
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

		$location = $player->getLocation();
		CopyTask::queue($selection, Position::fromObject($selection->getPos1(), $player->getLevelNonNull()), function (Selection $selection, Position $place, DynamicBlockListSelection $copy) use ($count, $location) {
			$pos = $selection->getPos1();

			for ($i = 1; $i <= $count; $i++) {
				if ($location->getPitch() >= 45) {
					$p = $pos->getSide(Vector3::SIDE_DOWN, $i);
				} elseif ($location->getPitch() <= -45) {
					$p = $pos->getSide(Vector3::SIDE_UP, $i);
				} elseif ($location->getYaw() >= 315 || $location->getYaw() < 45) {
					$p = $pos->getSide(Vector3::SIDE_SOUTH, $i);
				} elseif ($location->getYaw() >= 45 && $location->getYaw() < 135) {
					$p = $pos->getSide(Vector3::SIDE_WEST, $i);
				} elseif ($location->getYaw() >= 135 && $location->getYaw() < 225) {
					$p = $pos->getSide(Vector3::SIDE_NORTH, $i);
				} else {
					$p = $pos->getSide(Vector3::SIDE_EAST, $i);
				}

				PasteTask::queue($copy, Position::fromObject($p, $place->getLevelNonNull()));
			}
		});
	}
}