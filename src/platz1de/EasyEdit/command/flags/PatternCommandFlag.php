<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

/**
 * @extends ValuedCommandFlag<Pattern>
 */
class PatternCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return PatternCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): CommandFlag
	{
		try {
			$this->setArgument(PatternParser::parseInput($argument, $session->asPlayer()));
			return $this;
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
	}
}