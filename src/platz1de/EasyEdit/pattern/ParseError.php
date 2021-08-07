<?php

namespace platz1de\EasyEdit\pattern;

use UnexpectedValueException;

class ParseError extends UnexpectedValueException
{
	/**
	 * ParseError constructor.
	 * @param string   $message
	 * @param int|null $pos
	 * @param bool     $prefix
	 */
	public function __construct(string $message, ?int $pos = null, bool $prefix = true)
	{
		parent::__construct(($prefix ? "Parse Error: " : "") . $message . ($pos === null ? "" : " at Character " . $pos));
	}
}