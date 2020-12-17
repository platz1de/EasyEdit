<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class UndoCommand extends Command
{
	public function __construct()
	{
		parent::__construct("/undo", "Revert your lastest change", "//undo <count>", []);
		$this->setPermission("easyedit.command.undo");
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

		if (!HistoryManager::canUndo($sender->getName())) {
			Messages::send($sender, "no-history");
		}

		$count = min(100, (int)($args[0] ?? 1));

		for ($i = 0; $i < $count; $i++) {
			HistoryManager::undoStep($sender->getName());
		}
	}
}