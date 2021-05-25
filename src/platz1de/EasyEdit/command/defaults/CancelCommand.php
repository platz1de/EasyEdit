<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\PieceManager;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CancelCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cancel", "Cancel the current task", "easyedit.command.thread");
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		if (WorkerAdapter::cancel()) {
			Messages::send($player, "task-cancelled");
		} else {
			Messages::send($player, "no-task");
		}
	}
}