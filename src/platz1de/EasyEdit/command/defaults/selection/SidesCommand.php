<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SidesCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/sides", "Set sides of the selected area", "//sides [pattern]", ["/side"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 * @return Pattern
	 */
	public function parsePattern(Player $player, array $args): Pattern
	{
		return SidesPattern::from([ArgumentParser::parseCombinedPattern($player, $args, 0, "stone")]);
	}
}