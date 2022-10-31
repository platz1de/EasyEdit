<?php

namespace platz1de\EasyEdit\command\defaults\history;

use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\session\Session;

class UndoCommand extends HistoryAccessCommand
{
	public function __construct()
	{
		parent::__construct("/undo");
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$target = $flags->getSessionFlag("target");
		if (!$target->canUndo()) {
			$session->sendMessage("no-history");
		}

		$count = min(100, $flags->getIntFlag("count"));

		for ($i = 0; $i < $count; $i++) {
			$target->undoStep($session);
		}
	}
}