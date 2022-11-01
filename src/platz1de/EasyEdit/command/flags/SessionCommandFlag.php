<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager;

/**
 * @extends ValuedCommandFlag<Session>
 */
class SessionCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return SessionCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): CommandFlag
	{
		$this->setArgument(SessionManager::get($argument, false));
		return $this;
	}
}