<?php

namespace platz1de\EasyEdit\listener;

use platz1de\EasyEdit\EasyEdit;
use pocketmine\Server;

trait ToggleableEventListener
{
	public static function init(): void
	{
		Server::getInstance()->getPluginManager()->registerEvents(new self(), EasyEdit::getInstance());
	}
}