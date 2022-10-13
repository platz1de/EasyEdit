<?php

namespace platz1de\EasyEdit\command\flags;

use InvalidArgumentException;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;

class SingularCommandFlag extends CommandFlag
{
	public function needsArgument(): bool
	{
		return false;
	}

	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		throw new InvalidArgumentException("This flag does not need an argument");
	}
}