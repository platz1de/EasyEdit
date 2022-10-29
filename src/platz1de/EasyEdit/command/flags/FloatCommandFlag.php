<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\session\Session;

class FloatCommandFlag extends CommandFlag
{
	private float $argument;

	public function needsArgument(): bool
	{
		return true;
	}

	/**
	 * @param float $argument
	 */
	public function setArgument(float $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return float
	 */
	public function getArgument(): float
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return FloatCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		if (!is_numeric($argument)) {
			throw new InvalidUsageException($command);
		}
		$this->setArgument((float) $argument);
		return $this;
	}
}