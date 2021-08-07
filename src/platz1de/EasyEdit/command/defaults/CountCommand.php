<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\CountTask;
use pocketmine\player\Player;
use Throwable;

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
			$selection = new Sphere($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (int) $args[0]);
		} else {
			try {
				$selection = SelectionManager::getFromPlayer($player->getName());
				Selection::validate($selection);
			} catch (Throwable $exception) {
				Messages::send($player, "no-selection");
				return;
			}
		}

		CountTask::queue($selection, $player->getPosition());
	}
}