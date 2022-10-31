<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager;

class SessionCommandFlag extends CommandFlag
{
	private Session $argument;

	/**
	 * @param Session     $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return SessionCommandFlag
	 */
	public static function with(Session $argument, string $name, array $aliases = null, string $id = null): self
	{
		$flag = new self($name, $aliases, $id);
		$flag->hasArgument = true;
		$flag->argument = $argument;
		return $flag;
	}

	/**
	 * @param Session $argument
	 */
	public function setArgument(Session $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return Session
	 */
	public function getArgument(): Session
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return CommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): CommandFlag
	{
		$this->setArgument(SessionManager::get($argument, false));
		return $this;
	}
}