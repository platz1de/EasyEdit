<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;

class StringCommandFlag extends CommandFlag
{
	private string $argument;

	public function needsArgument(): bool
	{
		return true;
	}

	/**
	 * @param string $argument
	 */
	public function setArgument(string $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return string
	 */
	public function getArgument(): string
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return StringCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		$this->setArgument($argument);
		return $this;
	}
}