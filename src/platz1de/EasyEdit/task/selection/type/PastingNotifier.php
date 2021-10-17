<?php

namespace platz1de\EasyEdit\task\selection\type;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\AdditionalDataManager;

trait PastingNotifier
{
	/**
	 * @param string                $player
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, float $time, string $changed, AdditionalDataManager $data): void
	{
		Messages::send($player, "blocks-pasted", ["{time}" => (string) $time, "{changed}" => $changed]);
	}
}