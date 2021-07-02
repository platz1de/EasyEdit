<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\CountTask;
use pocketmine\Player;

class CountCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/count", "Count selected blocks", "easyedit.command.count", "//count [radius]");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (isset($args[0])) {
			$selection = new Sphere($player->getName(), $player->getLevelNonNull()->getFolderName(), $player->asVector3()->floor(), (int) $args[0]);
		} else {
			try {
				$selection = SelectionManager::getFromPlayer($player->getName());
				Selection::validate($selection);
			} catch (Exception $exception) {
				Messages::send($player, "no-selection");
				return;
			}
		}

		CountTask::queue($selection, $player);
	}
}