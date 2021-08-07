<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\task\selection\PasteTask;
use pocketmine\player\Player;
use Throwable;

class PasteCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/paste", "Paste the Clipboard", "easyedit.command.paste");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$selection = ClipBoardManager::getFromPlayer($player->getName());
		} catch (Throwable $exception) {
			Messages::send($player, "no-clipboard");
			return;
		}

		PasteTask::queue($selection, $player->getPosition());
	}
}