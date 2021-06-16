<?php

namespace platz1de\EasyEdit\task\selection\type;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\AdditionalDataManager;

trait SettingNotifier
{
	/**
	 * @param Selection             $selection
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public function notifyUser(Selection $selection, float $time, string $changed, AdditionalDataManager $data): void
	{
		Messages::send($selection->getPlayer(), "blocks-set", ["{time}" => $time, "{changed}" => $changed]);
	}
}