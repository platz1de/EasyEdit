<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

/**
 * @extends ValuedCommandFlag<string>
 */
class StringyPatternCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return StringyPatternCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): CommandFlag
	{
		try {
			PatternParser::validateInput($argument, $session->asPlayer());
			$this->setArgument($argument);
			return $this;
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
	}
}