<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\Player;

class SphereCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/sphere", "Set a sphere", "easyedit.command.set", "//sphere <radius> <pattern>");
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		if (($args[1] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$pattern = Pattern::parse($args[1]);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getName(), $player->asVector3(), $args[0]), $pattern, $player);
	}
}