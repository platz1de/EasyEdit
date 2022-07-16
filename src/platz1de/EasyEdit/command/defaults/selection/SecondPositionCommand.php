<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\world\Position;

class SecondPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos2", [KnownPermissions::PERMISSION_SELECT], ["/2"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		if (count($args) > 2) {
			$session->selectPos2(Position::fromObject(ArgumentParser::parseCoordinates($session, $args[0], $args[1], $args[2]), $session->asPlayer()->getPosition()->getWorld()));
		} else {
			$session->selectPos2($session->asPlayer()->getPosition());
		}
	}
}