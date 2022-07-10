<?php

namespace platz1de\EasyEdit\task\editing\type;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\AdditionalDataManager;

trait SettingNotifier
{
	/**
	 * @param int                   $taskId
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(int $taskId, string $time, string $changed, AdditionalDataManager $data): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-set", ["{time}" => $time, "{changed}" => $changed])));
	}
}