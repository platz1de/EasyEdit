<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\selection\WallPattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class WallCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/walls", "Set walls of the selected area", [KnownPermissions::PERMISSION_EDIT], "//walls [pattern]", ["/wall"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$pattern = PatternParser::parseInputCombined($args, 0, $player, "stone");
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(ArgumentParser::getSelection($player), WallPattern::from([$pattern]), $player->getPosition());
	}
}