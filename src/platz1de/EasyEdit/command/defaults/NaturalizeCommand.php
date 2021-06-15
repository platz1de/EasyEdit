<?php

namespace platz1de\EasyEdit\command\defaults;

use Exception;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\Player;

class NaturalizeCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize", "Naturalize the selected Area", "easyedit.command.set", "//naturalize [pattern] [pattern] [pattern]");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$top = Pattern::parse($args[0] ?? "grass");
			$middle = Pattern::parse($args[1] ?? "dirt");
			$bottom = Pattern::parse($args[2] ?? "stone");
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

		SetTask::queue($selection, new Pattern([new NaturalizePattern([$top, $middle, $bottom], [])], []), $player);
	}
}