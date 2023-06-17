<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;

class NoClipboardException extends CommandException
{
	public function sendWarning(Session $session, EasyEditCommand $command): void
	{
		$session->sendMessage("no-clipboard");
	}
}