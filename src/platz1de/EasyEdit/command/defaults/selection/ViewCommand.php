<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\utils\HighlightingManager;
use pocketmine\player\Player;
use Throwable;

class ViewCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/view", "View the selected area", [KnownPermissions::PERMISSION_SELECT], "//view", ["/show"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			Selection::validate($selection);
		} catch (Throwable) {
			Messages::send($player, "no-selection");
			return;
		}

		HighlightingManager::showStructureView($player->getName(), $player->getWorld(), $player->getPosition()->up(2), $selection->getPos1(), $selection->getPos2());
	}
}