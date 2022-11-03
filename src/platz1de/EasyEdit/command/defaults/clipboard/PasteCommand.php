<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FacingCommandFlag;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\DynamicStoredPasteTask;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;

class PasteCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/paste", [], [KnownPermissions::PERMISSION_CLIPBOARD, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new DynamicStoredPasteTask($session->getClipboard(), $session->asPlayer()->getPosition(), true, $flags->getIntFlag("mode")));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"default" => IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_ALL, "mode", [], "d", true),
			"insert" => IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_AIR, "mode", [], "i"),
			"merge" => IntegerCommandFlag::with(DynamicPasteTask::MODE_ONLY_SOLID, "mode", ["solid"], "m"),
			"replace" => IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_SOLID, "mode", [], "r")
		];
	}
}