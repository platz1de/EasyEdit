<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\editing\selection\Noise3DTask;
use pocketmine\player\Player;
use Throwable;

class NoiseCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/noise", "Generate with a simple noise function", "easyedit.command.generate", "//noise [type]");
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

		Noise3DTask::queue($selection, $player->getPosition());
	}
}