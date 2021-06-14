<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\logic\selection\WallPattern;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\Player;

class WallCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/walls", "Set walls of the selected area", "easyedit.command.set", "//walls [pattern]", ["/wall"]);
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$pattern = Pattern::processPattern(Pattern::parsePiece($args[0] ?? "stone"));
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			Selection::validate($selection);
		} catch (Exception $exception) {
			Messages::send($player, "no-selection");
			return;
		}

		SetTask::queue($selection, new Pattern([new WallPattern($pattern, [])], []), $player);
	}
}