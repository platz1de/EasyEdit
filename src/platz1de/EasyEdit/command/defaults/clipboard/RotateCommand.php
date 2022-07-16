<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\DynamicStoredRotateTask;

class RotateCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/rotate", [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(new DynamicStoredRotateTask($session->getClipboard()));
	}
}