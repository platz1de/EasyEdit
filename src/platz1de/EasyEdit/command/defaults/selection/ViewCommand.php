<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\world\clientblock\ClientSideBlockManager;
use platz1de\EasyEdit\world\clientblock\StructureBlockWindow;
use pocketmine\player\Player;

class ViewCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/view", [KnownPermissions::PERMISSION_SELECT], ["/show"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$selection = ArgumentParser::getCube($player);
		ClientSideBlockManager::registerBlock($player->getName(), new StructureBlockWindow($player, $selection->getPos1(), $selection->getPos2()));
	}
}