<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\PatternParser;
use platz1de\EasyEdit\selection\HollowSphere;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\player\Player;

class HollowSphereCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/hsphere", "Set a hollow sphere", "easyedit.command.set", "//hsphere <radius> <pattern> [thickness]", ["/hsph", "/hollowsphere"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (($args[1] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$pattern = PatternParser::parseInput($args[1], $player);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(new HollowSphere($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (int) $args[0], (int) ($args[2] ?? 1)), $pattern, $player->getPosition());
	}
}