<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\editing\selection\Noise3DTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class NoiseCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/noise", "Generate with a simple noise function", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], "//noise [type]");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		Noise3DTask::queue(ArgumentParser::getSelection($player), $player->getPosition());
	}
}