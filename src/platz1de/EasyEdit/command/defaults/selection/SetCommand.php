<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\SetTask;

class SetCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/set", [KnownPermissions::PERMISSION_EDIT]);
		$this->flagOrder = ["pattern" => true];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runSettingTask(new SetTask($session->getSelection(), $flags->getPatternFlag("pattern")));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"pattern" => new PatternCommandFlag("pattern", [], "p")
		];
	}
}