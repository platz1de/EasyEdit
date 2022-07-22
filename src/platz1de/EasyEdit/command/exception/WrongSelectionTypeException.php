<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\session\Session;

class WrongSelectionTypeException extends CommandException
{
	private string $given;
	private string $expected;

	public function __construct(string $given, string $expected)
	{
		$this->given = $given;
		$this->expected = $expected;
		parent::__construct("Wrong selection type " . $given . " given, expected " . $expected);
	}

	public function sendWarning(Session $session): void
	{
		$session->sendMessage("wrong-selection", ["{given}" => $this->given, "{expected}" => $this->expected]);
	}
}