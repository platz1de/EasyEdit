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
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new DynamicStoredFlipTask($session->getClipboard(), Facing::axis($flags->getIntFlag("direction")->getArgument())));
	}

	/**
	 * @return CommandFlag[]
	 */
	public function getKnownFlags() : array{
		return [
			"direction" => new FacingCommandFlag("direction", ["dir"], "d")
		];
	}
}