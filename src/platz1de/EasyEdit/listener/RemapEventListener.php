<?php

namespace platz1de\EasyEdit\listener;

use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;

/**
 * Remaps usages with more than two slashes to accept autocomplete
 * Tanks mojang for your broken command handling...
 */
class RemapEventListener implements Listener
{
	use ToggleableEventListener;

	public function onCommand(CommandEvent $event): void
	{
		$event->setCommand(preg_replace("/^\/+/", "/", $event->getCommand()) ?? "");
	}
}