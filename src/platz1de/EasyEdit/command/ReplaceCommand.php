<?php

namespace platz1de\EasyEdit\command;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Block;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\SetTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ReplaceCommand extends Command
{
	public function __construct()
	{
		parent::__construct("/replace", "Replace the selected Area", "//replace <block> <pattern>", []);
		$this->setPermission("easyedit.command.set");
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

		if (($args[1] ?? "") === "") {
			$sender->sendMessage($this->getUsage());
			return;
		}

		try {
			$block = Pattern::getBlock($args[0]);
			$pattern = Pattern::processPattern(Pattern::parsePiece($args[1]));
		} catch (ParseError $exception) {
			$sender->sendMessage($exception->getMessage());
			return;
		}

		try {
			$selection = SelectionManager::getFromPlayer($sender->getName());
		} catch (Exception $exception) {
			Messages::send($sender, "no-selection");
			return;
		}

		WorkerAdapter::submit(new SetTask($selection, new Pattern([new Block($pattern, [$block])], []), $sender));
	}
}