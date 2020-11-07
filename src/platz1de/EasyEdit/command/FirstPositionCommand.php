<?php

namespace platz1de\EasyEdit\command;

use Exception;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\SetTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class FirstPositionCommand extends Command
{
	public function __construct()
	{
		parent::__construct("/pos1", "Set the first Position", "//pos1", ["/1"]);
		$this->setPermission("easyedit.position");
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

		EasyEdit::selectPos1($sender, $sender->floor());
	}
}