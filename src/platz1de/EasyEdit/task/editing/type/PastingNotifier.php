<?php

namespace platz1de\EasyEdit\task\editing\type;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\session\MessageSendData;

trait PastingNotifier
{
	/**
	 * @param int    $taskId
	 * @param string $time
	 * @param string $changed
	 */
	public static function notifyUser(int $taskId, string $time, string $changed): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-pasted", ["{time}" => $time, "{changed}" => $changed])));
	}
}