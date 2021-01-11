<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class RedoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/redo", "Revert your latest undo", "easyedit.command.redo", "//redo <count>");
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		if (!HistoryManager::canRedo($player->getName())) {
			Messages::send($player, "no-future");
		}

		$count = min(100, (int)($args[0] ?? 1));

		for ($i = 0; $i < $count; $i++) {
			HistoryManager::redoStep($player->getName());
		}
	}
}