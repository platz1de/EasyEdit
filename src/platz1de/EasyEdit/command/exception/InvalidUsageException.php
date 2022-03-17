<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use UnexpectedValueException;

class InvalidUsageException extends CommandException
{
	public function __construct(EasyEditCommand $command)
	{
		if ($command->getUsage() instanceof Translatable) {
			throw new UnexpectedValueException("EasyEdit commands should not use translatable usages");
		}
		parent::__construct($command->getCompactHelp());
	}

	public function sendWarning(Player $player): void
	{
		Messages::send($player, "wrong-usage", ["{usage}" => $this->getMessage()]);
	}
}