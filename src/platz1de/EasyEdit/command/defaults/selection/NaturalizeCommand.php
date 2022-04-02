<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use pocketmine\player\Player;

class NaturalizeCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 * @return Pattern
	 */
	public function parsePattern(Player $player, array $args): Pattern
	{
		try {
			$top = PatternParser::parseInput($args[0] ?? "grass", $player);
			$middle = PatternParser::parseInput($args[1] ?? "dirt", $player);
			$bottom = PatternParser::parseInput($args[2] ?? "stone", $player);
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}

		return NaturalizePattern::from([$top, $middle, $bottom]);
	}
}