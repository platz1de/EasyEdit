<?php

namespace platz1de\EasyEdit\command\exception;

use InvalidArgumentException;
use pocketmine\player\Player;

/**
 * Exceptions thrown if commands could not be executed correctly
 */
abstract class CommandException extends InvalidArgumentException
{
	public function sendWarning(Player $player): void { }
}