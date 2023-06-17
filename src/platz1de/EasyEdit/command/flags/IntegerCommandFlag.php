<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\session\Session;

/**
 * @extends ValuedCommandFlag<int>
 */
class IntegerCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return IntegerCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): self
	{
		if (!is_numeric($argument)) {
			throw new InvalidUsageException();
		}
		$this->setArgument((int) $argument);
		return $this;
	}
}