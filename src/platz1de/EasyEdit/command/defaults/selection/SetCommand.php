<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class SetCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/set", [KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 1, $this);
		$session->runTask(SetTask::from($session->asPlayer()->getWorld()->getFolderName(), $session->getSelection(), $session->asPlayer()->getPosition(), ArgumentParser::parseCombinedPattern($session, $args, 0)));
	}
}