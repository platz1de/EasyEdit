<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\CountTask;

class CountCommand extends SphericalSelectionCommand
{
	public function __construct()
	{
		parent::__construct("/count", [KnownPermissions::PERMISSION_SELECT]);
	}

	/**
	 * @param Session   $session
	 * @param Selection $selection
	 */
	public function processSelection(Session $session, Selection $selection): void
	{
		$session->runTask(new CountTask($selection));
	}
}