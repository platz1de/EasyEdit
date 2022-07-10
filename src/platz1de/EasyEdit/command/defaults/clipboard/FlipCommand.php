<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\DynamicStoredFlipTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\math\Facing;

class FlipCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/flip", [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(DynamicStoredFlipTask::from($session->getClipboard(), Facing::axis(ArgumentParser::parseFacing($session, $args[0] ?? null))));
	}
}