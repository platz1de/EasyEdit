<?php

namespace platz1de\EasyEdit\command;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\task\selection\PasteTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class PasteCommand extends Command
{
	public function __construct()
	{
		parent::__construct("/paste", "Paste the Clipboard", "//paste", []);
		$this->setPermission("easyedit.command.paste");
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

		try {
			$selection = ClipBoardManager::getFromPlayer($sender->getName());
		} catch (Exception $exception) {
			Messages::send($sender, "no-clipboard");
			return;
		}

		WorkerAdapter::submit(new PasteTask($selection, $sender));
	}
}