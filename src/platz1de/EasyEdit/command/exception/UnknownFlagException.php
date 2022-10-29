<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;
use pocketmine\lang\Translatable;
use UnexpectedValueException;

class UnknownFlagException extends CommandException
{
	private EasyEditCommand $command;

	public function __construct(string $flag, EasyEditCommand $command)
	{
		parent::__construct($flag);
		$this->command = $command;
	}

	public function sendWarning(Session $session): void
	{
		if ($this->command->getUsage() instanceof Translatable) {
			throw new UnexpectedValueException("EasyEdit commands should not use translatable usages");
		}
		$session->sendMessage("unknown-flag", ["{flag}" => $this->getMessage(), "{usage}" => $this->command->getUsage()]);
	}
}