<?php

namespace platz1de\EasyEdit\command;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\FillTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class FillCommand extends Command
{
	public function __construct()
	{
		parent::__construct("fill", "Fill the selected Area", "/fill <pattern>", ["set"]);
		$this->setPermission("easyedit.command.fill");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		if (!$this->testPermission($sender)) {
			return;
		}

		if (($args[0] ?? "") === "") {
			$sender->sendMessage($this->getUsage());
			return;
		}

		try {
			$pattern = Pattern::parse($args[0]);
		}catch (ParseError $exception){
			$sender->sendMessage($exception->getMessage());
			return;
		}

		try {
			$selection = SelectionManager::getFromPlayer($sender->getName());
		} catch (Exception $exception) {
			Messages::send($sender, "no-selection");
			return;
		}

		var_dump($pattern);

		WorkerAdapter::submit(new FillTask($selection, $pattern));
	}
}