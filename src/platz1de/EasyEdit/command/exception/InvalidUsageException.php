<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use pocketmine\player\Player;

class InvalidUsageException extends CommandException
{
	public function __construct(EasyEditCommand $command)
	{
		parent::__construct($command->getUsage());
	}

	public function sendWarning(Player $player): void
	{
		Messages::send($player, "wrong-usage", ["{usage}" => $this->getMessage()]);
	}
}