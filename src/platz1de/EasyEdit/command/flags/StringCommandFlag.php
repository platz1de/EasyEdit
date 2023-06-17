<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\session\Session;

/**
 * @extends ValuedCommandFlag<string>
 */
class StringCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return StringCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): self
	{
		$this->setArgument($argument);
		return $this;
	}
}