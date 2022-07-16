<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;

abstract class AliasedPatternCommand extends EasyEditCommand
{
	/**
	 * @param string   $name
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $aliases = [])
	{
		parent::__construct($name, [KnownPermissions::PERMISSION_EDIT], $aliases);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(new SetTask($session->getSelection(), $this->parsePattern($session, $args)));
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 * @return Pattern
	 */
	abstract public function parsePattern(Session $session, array $args): Pattern;
}