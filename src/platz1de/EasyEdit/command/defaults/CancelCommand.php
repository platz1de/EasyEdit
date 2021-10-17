<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use pocketmine\player\Player;

class CancelCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cancel", "Cancel the current task", "easyedit.command.thread");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (BenchmarkManager::isRunning()) {
			Messages::send($player, "benchmark-cancel");
		} elseif (WorkerAdapter::cancel()) {
			Messages::send($player, "task-cancelled");
		} else {
			Messages::send($player, "no-task");
		}
	}
}