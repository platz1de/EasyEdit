<?php

namespace platz1de\EasyEdit\command\flags;

use InvalidArgumentException;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;

class SingularCommandFlag extends CommandFlag
{
	public function needsArgument(): bool
	{
		return false;
	}

	public function parseArgument(Session $session, string $argument): self
	{
		throw new InvalidArgumentException("This flag does not need an argument");
	}

	public function fits(string $argument): bool
	{
		return in_array(strtolower($argument), ["true", "t", "yes", "y", "1", "+"], true);
	}
}