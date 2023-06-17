<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;
use pocketmine\lang\Translatable;
use UnexpectedValueException;

class UnknownFlagException extends CommandException
{
	public function __construct(string $flag)
	{
		parent::__construct($flag);
	}

	public function sendWarning(Session $session, EasyEditCommand $command): void
	{
		if ($command->getUsage() instanceof Translatable) {
			throw new UnexpectedValueException("EasyEdit commands should not use translatable usages");
		}
		$session->sendMessage("unknown-flag", ["{flag}" => $this->getMessage(), "{usage}" => $command->getUsage()]);
	}
}