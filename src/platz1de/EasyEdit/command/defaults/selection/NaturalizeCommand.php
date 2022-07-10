<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

class NaturalizeCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize");
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 * @return Pattern
	 */
	public function parsePattern(Session $session, array $args): Pattern
	{
		try {
			$top = PatternParser::parseInput($args[0] ?? "grass", $session->asPlayer());
			$middle = PatternParser::parseInput($args[1] ?? "dirt", $session->asPlayer());
			$bottom = PatternParser::parseInput($args[2] ?? "stone", $session->asPlayer());
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}

		return new NaturalizePattern($top, $middle, $bottom);
	}
}