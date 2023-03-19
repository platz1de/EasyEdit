<?php

namespace platz1de\EasyEdit\command;

use Generator;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\session\Session;

class FlagRemapAlias extends EasyEditCommand
{
	public function __construct(private EasyEditCommand $parent, private CommandFlag $flag, string $name, array $aliases = [])
	{
		parent::__construct($name, $parent->getPermissions(), $aliases);
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag($this->flag->getName())) {
			$flags->removeFlag($this->flag->getName());
		}
		$flags->addFlag($this->flag);
		$this->parent->process($session, $flags);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return $this->parent->getKnownFlags($session);
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from $this->parent->parseArguments($flags, $session, $args);
	}
}