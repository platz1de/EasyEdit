<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\CopyTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class CopyCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/copy", [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new CopyTask($session->getSelection(), ArgumentParser::parseRelativePosition($session, $args[0] ?? null)));
	}

	/**
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(): array
	{
		return [
			"center" => new SingularCommandFlag("relative", [], "c"),
		];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		// TODO: Implement parseArguments() method.
	}
}