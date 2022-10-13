<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\session\Session;

class UnknownFlagException extends CommandException
{
	public function __construct(string $flag)
	{
		parent::__construct($flag);
	}

	public function sendWarning(Session $session): void
	{
		$session->sendMessage("unknown-flag", ["{name}" => $this->getMessage()]);
	}
}