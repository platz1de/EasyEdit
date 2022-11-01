<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\VectorCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\CopyTask;

class CopyCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/copy", [], [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new CopyTask($session->getSelection(), $flags->getVectorFlag("relative")));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"center" => VectorCommandFlag::with($session->getSelection()->getBottomCenter(), "relative", [], "c"),
			"position" => VectorCommandFlag::default($session->asPlayer()->getPosition(), "relative", [], "p")
		];
	}
}