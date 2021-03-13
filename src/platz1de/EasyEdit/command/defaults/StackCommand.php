<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\selection\StackedCube;
use platz1de\EasyEdit\task\selection\CopyTask;
use platz1de\EasyEdit\task\selection\PasteTask;
use platz1de\EasyEdit\task\selection\StackTask;
use platz1de\EasyEdit\utils\VectorUtils;
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

		StackTask::queue(new StackedCube($selection, VectorUtils::moveVectorInSight($player->getLocation(), new Vector3(), $count)), $player->asPosition());
	}
}