<?php

namespace platz1de\EasyEdit\pattern\parser;

use UnexpectedValueException;

class ParseError extends UnexpectedValueException
{

	/**
	 * ParseError constructor.
	 * @param string $message
	 * @param bool   $prefix
	 */
	public function __construct(string $message, bool $prefix = true)
	{
		parent::__construct(($prefix ? "Parse Error: " : "") . $message);
	}
}