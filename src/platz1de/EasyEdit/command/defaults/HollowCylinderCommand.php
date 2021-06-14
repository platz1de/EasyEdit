<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\HollowCylinder;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\Player;

class HollowCylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/hcylinder", "Set a hollow cylinder", "easyedit.command.set", "//hcylinder <radius> <height> <pattern> [thickness]", ["/hcy", "/hollowcylinder"]);
	}

	/**
	 * @param Player $player
	 * @param array  $args
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

		SetTask::queue(new HollowCylinder($player->getName(), $player->getLevelNonNull()->getFolderName(), $player->asVector3()->floor(), (int) $args[0], (int) $args[1], (int) ($args[3] ?? 1)), $pattern, $player);
	}
}