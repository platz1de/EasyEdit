<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;
use pocketmine\lang\Translatable;
use UnexpectedValueException;

class UnknownFlagExcaption extends CommandException
{
	public function __construct(string $flag)
	{
		if ($command->getUsage() instanceof Translatable) {
			throw new UnexpectedValueException("EasyEdit commands should not use translatable usages");
		}
		parent::__construct($command->getUsage());
	}

	public function sendWarning(Session $session): void
	{
		$session->sendMessage("unknown-flag", ["{name}" => $this->getMessage()]);
	}
}