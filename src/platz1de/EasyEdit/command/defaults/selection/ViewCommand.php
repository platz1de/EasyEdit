<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\world\HighlightingManager;
use pocketmine\player\Player;

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
		$selection = ArgumentParser::getCube($player);
		HighlightingManager::showStructureView($player, $player->getWorld(), $player->getPosition()->up(2), $selection->getPos1(), $selection->getPos2());
	}
}