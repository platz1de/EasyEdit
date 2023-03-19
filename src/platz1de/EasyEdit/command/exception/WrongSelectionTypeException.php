<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\session\Session;

class WrongSelectionTypeException extends CommandException
{
	public function __construct(private string $given, private string $expected)
	{
		parent::__construct("Wrong selection type " . $given . " given, expected " . $expected);
	}

	public function sendWarning(Session $session): void
	{
		$session->sendMessage("wrong-selection", ["{given}" => $this->given, "{expected}" => $this->expected]);
	}
}