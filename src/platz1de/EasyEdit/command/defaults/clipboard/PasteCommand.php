<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\DynamicStoredPasteTask;

class PasteCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/paste", [KnownPermissions::PERMISSION_CLIPBOARD, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(new DynamicStoredPasteTask($session->getClipboard(), $session->asPlayer()->getPosition(), true));
	}
}