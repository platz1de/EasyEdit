<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\CutTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class CutCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cut", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(CutTask::from($session->getSelection()->getWorldName(), $session->getSelection(), ArgumentParser::parseRelativePosition($session, $args[0] ?? null)));
	}
}