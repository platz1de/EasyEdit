<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\world\Position;

class GenericPositionCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	public function __construct(private int $number)
	{
		parent::__construct("/pos$number", [KnownPermissions::PERMISSION_SELECT], ["/$number"]);
		$this->flagOrder = ["x" => false, "y" => false, "z" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag("x") && $flags->hasFlag("y") && $flags->hasFlag("z")) {
			$session->selectPos(Position::fromObject(ArgumentParser::parseCoordinates($session, $flags->getStringFlag("x"), $flags->getStringFlag("y"), $flags->getStringFlag("z")), $session->asPlayer()->getPosition()->getWorld()), $this->number);
		} else {
			$session->selectPos($session->asPlayer()->getPosition(), $this->number);
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