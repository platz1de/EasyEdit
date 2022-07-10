<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\expanding\DrainTask;

class DrainCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/drain", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(DrainTask::from($session->asPlayer()->getWorld()->getFolderName(), null, $session->asPlayer()->getPosition()->asVector3()));
	}
}