<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\thread\output\OutputData;

abstract class SessionOutputData extends OutputData
{
	final public function handle(): void
	{
		EditHandler::processSessionOutput($this);
	}

	abstract public function handleSession(Session $session): void;
}