<?php

namespace platz1de\EasyEdit\command;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\CopyTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class CopyCommand extends Command
{
	public function __construct()
	{
		parent::__construct("/copy", "Copy the selected Area", "//copy", []);
		$this->setPermission("easyedit.command.copy");
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
			$selection = SelectionManager::getFromPlayer($sender->getName());
			/** @var Cube $selection */
			Selection::validate($selection, Cube::class);
		} catch (Exception $exception) {
			Messages::send($sender, "no-selection");
			return;
		}

		WorkerAdapter::submit(new CopyTask($selection, $sender));
	}
}