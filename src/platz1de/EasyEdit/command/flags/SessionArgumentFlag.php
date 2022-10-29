<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager;

class SessionArgumentFlag extends CommandFlag
{
	private Session $argument;

	public function needsArgument(): bool
	{
		return true;
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