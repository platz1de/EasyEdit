<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\thread\ThreadStats;

class StatusCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/status", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		//TODO: restart, shutdown, start, kill (other command?)
		ThreadStats::getInstance()->sendStatusMessage($session);
	}
}