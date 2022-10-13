<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\session\Session;

class IntCommandFlag extends CommandFlag
{
	private int $argument;

	public function needsArgument(): bool
	{
		return true;
	}

	/**
	 * @param int $argument
	 */
	public function setArgument(int $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return int
	 */
	public function getArgument(): int
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return IntCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		if (!is_numeric($argument)) {
			throw new InvalidUsageException($command);
		}
		$this->setArgument((int) $argument);
	}
}