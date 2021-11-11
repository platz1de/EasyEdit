<?php

namespace platz1de\EasyEdit\selection;

use UnexpectedValueException;

class WrongSelectionTypeError extends UnexpectedValueException
{
	public function __construct(string $given, string $expected)
	{
		parent::__construct("Wrong Selection of type " . $given . " given, expected " . $expected);
	}
}