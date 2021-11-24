<?php

namespace platz1de\EasyEdit\pattern\parser;

class WrongPatternUsageException extends ParseError
{
	/**
	 * @param string $message
	 */
	public function __construct(string $message)
	{
		parent::__construct("Wrong Pattern Usage: " . $message, false);
	}
}