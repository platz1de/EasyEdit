<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\world\clientblock\ClientSideBlockManager;
use platz1de\EasyEdit\world\clientblock\StructureBlockWindow;

class ViewCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/view", [KnownPermissions::PERMISSION_SELECT], ["/show"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$selection = $session->getCube();

		ClientSideBlockManager::registerBlock($session->getPlayer(), new StructureBlockWindow($session->asPlayer(), $selection->getPos1(), $selection->getPos2()));
	}
}