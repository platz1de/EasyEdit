<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\command\exception\CommandException;
use platz1de\EasyEdit\Messages;
use pocketmine\player\Player;

class WrongSelectionTypeException extends CommandException
{
	private string $given;
	private string $expected;

	public function __construct(string $given, string $expected)
	{
		$this->given = $given;
		$this->expected = $expected;
		parent::__construct("Wrong selection type " . $given . " given, expected " . $expected);
	}

	public function sendWarning(Player $player): void
	{
		Messages::send($player, "wrong-selection", ["{given}" => $this->given, "{expected}" => $this->expected]);
	}
}