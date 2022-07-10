<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class SphereCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/sphere", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/sph"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 2, $this);
		$session->runTask(SetTask::from($session->asPlayer()->getWorld()->getFolderName(), null, Sphere::aroundPoint($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), (int) $args[0]), $session->asPlayer()->getPosition(), ArgumentParser::parseCombinedPattern($session, $args, 1)));
	}
}