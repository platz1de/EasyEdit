<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\thread\input\task\CancelTaskData;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
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
		} else {
			//TODO: check if task is running
			CancelTaskData::from();
			Messages::send($player, "task-cancelled");
		}
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [];
	}
}