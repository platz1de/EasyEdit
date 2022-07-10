<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;

class FirstPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos1", [KnownPermissions::PERMISSION_SELECT], ["/1"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		if (count($args) > 2) {
			Cube::selectPos1($session->asPlayer(), ArgumentParser::parseCoordinates($session, $args[0], $args[1], $args[2]));
		} else {
			Cube::selectPos1($session->asPlayer(), $session->asPlayer()->getPosition()->floor());
		}
	}
}