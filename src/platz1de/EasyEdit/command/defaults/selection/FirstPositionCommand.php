<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\world\Position;

class FirstPositionCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/pos1", ["x" => false, "y" => false, "z" => false], [KnownPermissions::PERMISSION_SELECT], ["/1"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag("x") && $flags->hasFlag("y") && $flags->hasFlag("z")) {
			$session->selectPos1(Position::fromObject(ArgumentParser::parseCoordinates($session, $flags->getStringFlag("x"), $flags->getStringFlag("y"), $flags->getStringFlag("z")), $session->asPlayer()->getPosition()->getWorld()));
		} else {
			$session->selectPos1($session->asPlayer()->getPosition());
		}
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		//TODO: Turn these into integers (handle special cases like ~ and ^ while parsing flags)
		return [
			"x" => new StringCommandFlag("x"),
			"y" => new StringCommandFlag("y"),
			"z" => new StringCommandFlag("z")
		];
	}
}