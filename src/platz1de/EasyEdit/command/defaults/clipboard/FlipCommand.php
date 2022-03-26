<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\DynamicStoredFlipTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\math\Facing;
use pocketmine\player\Player;

class FlipCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/flip", [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		DynamicStoredFlipTask::queue($player->getName(), ArgumentParser::getClipboard($player), Facing::axis(ArgumentParser::parseFacing($player, $args[0] ?? null)));
	}
}