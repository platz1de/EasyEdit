<?php

namespace platz1de\EasyEdit\pattern;

class WrongPatternUsageException extends ParseError
{
	/**
	 * @param string $message
	 */
	public function __construct(string $message)
	{
		parent::__construct("Wrong Pattern Usage: " . $message, null, false);
	}
}