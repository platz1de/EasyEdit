<?php

namespace platz1de\EasyEdit\command\exception;

use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\utils\Messages;
use pocketmine\player\Player;

class PatternParseException extends CommandException
{
	public function __construct(ParseError $error)
	{
		parent::__construct($error->getMessage());
	}

	public function sendWarning(Player $player): void
	{
		Messages::send($player, $this->getMessage(), [], false);
	}
}