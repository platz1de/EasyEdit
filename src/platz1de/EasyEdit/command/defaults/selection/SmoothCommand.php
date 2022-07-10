<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\SmoothTask;

class SmoothCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/smooth", [KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		SmoothTask::queue($session, $session->getSelection(), $session->asPlayer()->getPosition());
	}
}