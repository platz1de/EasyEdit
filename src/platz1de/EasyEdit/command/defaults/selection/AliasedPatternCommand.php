<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;

abstract class AliasedPatternCommand extends SimpleFlagArgumentCommand
{
	/**
	 * @param string   $name
	 * @param bool[]   $flagOrder
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $flagOrder = [], array $aliases = [])
	{
		$flagOrder["pattern"] = true;
		parent::__construct($name, $flagOrder, [KnownPermissions::PERMISSION_EDIT], $aliases);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new SetTask($session->getSelection(), $flags->getPatternFlag("pattern")));
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 * @return Pattern
	 */
	abstract public function parsePattern(Session $session, CommandFlagCollection $flags): Pattern;

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