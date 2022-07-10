<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\editing\selection\Noise3DTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class NoiseCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/noise", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		Noise3DTask::queue($session, $session->getSelection(), $session->asPlayer()->getPosition());
	}
}