<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\MoveTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class MoveCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/move", [KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(new MoveTask($session->getSelection(), ArgumentParser::parseDirectionVector($session, $args[0] ?? null, $args[1] ?? null)));
	}
}