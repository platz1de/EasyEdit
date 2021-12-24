<?php

namespace platz1de\EasyEdit\listener;

use platz1de\EasyEdit\command\CommandManager;
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
		preg_match("/^\/*(\S*)/", $event->getCommand(), $matches);
		if (CommandManager::isKnownCommand("/" . $matches[1])) {
			$event->setCommand(preg_replace("/^\/+/", "/", $event->getCommand()) ?? "");
		}
	}
}