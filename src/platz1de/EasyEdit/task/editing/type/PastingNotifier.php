<?php

namespace platz1de\EasyEdit\task\editing\type;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\AdditionalDataManager;

trait PastingNotifier
{
	/**
	 * @param string                $player
	 * @param float                 $time
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, float $time, int $changed, AdditionalDataManager $data): void
	{
		MessageSendData::from($player, Messages::replace("blocks-pasted", ["{time}" => (string) $time, "{changed}" => (string) $changed]));
	}
}