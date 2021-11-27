<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use pocketmine\player\Player;
use Throwable;

class NaturalizeCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize", "Naturalize the selected Area", [KnownPermissions::PERMISSION_EDIT], "//naturalize [pattern] [pattern] [pattern]");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$top = PatternParser::parseInput($args[0] ?? "grass", $player);
			$middle = PatternParser::parseInput($args[1] ?? "dirt", $player);
			$bottom = PatternParser::parseInput($args[2] ?? "stone", $player);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			Selection::validate($selection);
		} catch (Throwable) {
			Messages::send($player, "no-selection");
			return;
		}

		SetTask::queue($selection, NaturalizePattern::from([$top, $middle, $bottom]), $player->getPosition());
	}
}