<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\thread\input\task\CancelTaskData;

class CancelCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cancel", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		if (BenchmarkManager::isRunning()) {
			$session->sendMessage("benchmark-cancel");
		} else {
			//TODO: check if task is running
			CancelTaskData::from();
			$session->sendMessage("task-cancelled");
		}
	}
}