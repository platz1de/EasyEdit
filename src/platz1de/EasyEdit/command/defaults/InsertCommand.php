<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\task\selection\InsertTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\Player;

class InsertCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/insert", "Insert the Clipboard", "easyedit.command.paste");
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		try {
			$selection = ClipBoardManager::getFromPlayer($player->getName());
		} catch (Exception $exception) {
			Messages::send($player, "no-clipboard");
			return;
		}

		WorkerAdapter::submit(new InsertTask($selection, $player));
	}
}