<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\expanding\PasteBlockStatesTask;

class PasteStatesCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pastestates", [KnownPermissions::PERMISSION_MANAGE, KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT]);
	}

	public function process(Session $session, array $args): void
	{
		$session->runTask(PasteBlockStatesTask::from($session->asPlayer()->getWorld()->getFolderName(), null, $session->asPlayer()->getPosition()));
	}
}