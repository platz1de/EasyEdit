<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\pattern\logic\selection\WallPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class WallCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/walls", ["/wall"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 * @return Pattern
	 */
	public function parsePattern(Player $player, array $args): Pattern
	{
		return WallPattern::from([ArgumentParser::parseCombinedPattern($player, $args, 0, "stone")]);
	}
}