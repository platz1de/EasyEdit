<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\session\Session;

class PatternParseException extends CommandException
{
	public function __construct(ParseError $error)
	{
		parent::__construct($error->getMessage());
	}

	public function sendWarning(Session $session, EasyEditCommand $command): void
	{
		$session->sendMessage("pattern-invalid", ["{message}" => $this->getMessage()]);
	}
}