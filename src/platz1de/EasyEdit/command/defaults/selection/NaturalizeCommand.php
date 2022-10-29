<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

class NaturalizeCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize", ["top" => false, "middle" => false, "bottom" => false], ["/nat"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 * @return Pattern
	 */
	public function parsePattern(Session $session, CommandFlagCollection $flags): Pattern
	{
		if (!$flags->hasFlag("top")) {
			$flags->addFlag((new PatternCommandFlag("top"))->parseArgument($this, $session, "grass"));
		}
		if (!$flags->hasFlag("middle")) {
			$flags->addFlag((new PatternCommandFlag("middle"))->parseArgument($this, $session, "dirt"));
		}
		if (!$flags->hasFlag("bottom")) {
			$flags->addFlag((new PatternCommandFlag("bottom"))->parseArgument($this, $session, "stone"));
		}
		return new NaturalizePattern($flags->getPatternFlag("top"), $flags->getPatternFlag("middle"), $flags->getPatternFlag("bottom"));
	}

	public function getKnownFlags(Session $session): array
	{
		return array_merge(parent::getKnownFlags($session), [
			"top" => new PatternCommandFlag("top", [], "t"),
			"middle" => new PatternCommandFlag("middle", [], "m"),
			"bottom" => new PatternCommandFlag("bottom", [], "b"),
		]);
	}
}