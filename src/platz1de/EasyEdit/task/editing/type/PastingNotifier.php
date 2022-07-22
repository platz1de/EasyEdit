<?php

namespace platz1de\EasyEdit\task\editing\type;

use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\Messages;

trait PastingNotifier
{
	abstract public function getTaskId(): int;

	/**
	 * @param string $time
	 * @param string $changed
	 */
	public function notifyUser(string $time, string $changed): void
	{
		$this->sendOutputPacket(new MessageSendData(Messages::replace("blocks-pasted", ["{time}" => $time, "{changed}" => $changed])));
	}
}