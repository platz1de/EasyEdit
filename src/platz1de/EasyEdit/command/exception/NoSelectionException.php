<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\Messages;
use pocketmine\player\Player;

class NoSelectionException extends CommandException
{
	public function __construct()
	{
		parent::__construct("No area selected");
	}

	public function sendWarning(Player $player): void
	{
		Messages::send($player, "no-selection");
	}
}