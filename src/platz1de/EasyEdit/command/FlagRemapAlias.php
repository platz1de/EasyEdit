<?php

namespace platz1de\EasyEdit\command;

use Generator;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\session\Session;

class FlagRemapAlias extends EasyEditCommand
{
	private EasyEditCommand $parent;
	private CommandFlag $flag;

	public function __construct(EasyEditCommand $alias, CommandFlag $flag, string $name, array $aliases = [])
	{
		parent::__construct($name, $alias->getPermissions(), $aliases);
		$this->parent = $alias;
		$this->flag = $flag;
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