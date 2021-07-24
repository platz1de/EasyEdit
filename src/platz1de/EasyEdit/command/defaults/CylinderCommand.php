<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\player\Player;

class CylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cylinder", "Set a cylinder", "easyedit.command.set", "//cylinder <radius> <height> <pattern>", ["/cy"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (($args[2] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$pattern = Pattern::parse($args[2]);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(new Cylinder($player->getName(), $player->getWorld()->getFolderName(), $player->asVector3()->floor(), (int) $args[0], (int) $args[1]), $pattern, $player->getPosition());
	}
}