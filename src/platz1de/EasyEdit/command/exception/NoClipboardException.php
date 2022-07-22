<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\session\Session;

class NoClipboardException extends CommandException
{
	public function __construct()
	{
		parent::__construct("No area copied");
	}

	public function sendWarning(Session $session): void
	{
		$session->sendMessage("no-clipboard");
	}
}