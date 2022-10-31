<?php

namespace platz1de\EasyEdit\command\defaults\history;

use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\session\Session;

class RedoCommand extends HistoryAccessCommand
{
	public function __construct()
	{
		parent::__construct("/redo");
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$target = $flags->getSessionFlag("target");
		if (!$target->canRedo()) {
			$session->sendMessage("no-future");
		}

		$count = min(100, $flags->getIntFlag("count"));

		for ($i = 0; $i < $count; $i++) {
			$target->redoStep($session);
		}
	}
}