<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class CylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cylinder", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/cy"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 3, $this);
		SetTask::queue($session, Cylinder::aroundPoint($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), (float) $args[0], (int) $args[1]), ArgumentParser::parseCombinedPattern($session, $args, 2), $session->asPlayer()->getPosition());
	}
}