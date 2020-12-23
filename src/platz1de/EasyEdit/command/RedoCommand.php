<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class RedoCommand extends Command
{
	public function __construct()
	{
		parent::__construct("/redo", "Revert your latest undo", "//redo <count>", []);
		$this->setPermission("easyedit.command.redo");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		if (!$sender instanceof Player || !$this->testPermission($sender)) {
			return;
		}

		if (!HistoryManager::canRedo($sender->getName())) {
			Messages::send($sender, "no-future");
		}

		$count = min(100, (int)($args[0] ?? 1));

		for ($i = 0; $i < $count; $i++) {
			HistoryManager::redoStep($sender->getName());
		}
	}
}