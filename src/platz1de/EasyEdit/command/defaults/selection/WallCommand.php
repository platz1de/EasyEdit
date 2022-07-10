<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\pattern\logic\selection\WallPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;

class WallCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/walls", ["/wall"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 * @return Pattern
	 */
	public function parsePattern(Session $session, array $args): Pattern
	{
		return new WallPattern(1, [ArgumentParser::parseCombinedPattern($session, $args, 0, "stone")]);
	}
}