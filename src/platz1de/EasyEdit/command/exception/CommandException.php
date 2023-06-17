<?php

namespace platz1de\EasyEdit\command\exception;

use InvalidArgumentException;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;

/**
 * Exceptions thrown if commands could not be executed correctly
 */
abstract class CommandException extends InvalidArgumentException
{
	public function sendWarning(Session $session, EasyEditCommand $command): void { }
}