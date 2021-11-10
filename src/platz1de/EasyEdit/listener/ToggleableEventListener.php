<?php

namespace platz1de\EasyEdit\listener;

use platz1de\EasyEdit\command\CommandManager;
use platz1de\EasyEdit\EasyEdit;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\Server;
use Throwable;

class ToggleableEventListener implements Listener
{
	public static function enableCommandSuggestions(): void
	{
		try {
			Server::getInstance()->getPluginManager()->registerEvent(DataPacketSendEvent::class, static function (DataPacketSendEvent $event): void {
				foreach ($event->getPackets() as $packet) {
					if ($packet instanceof AvailableCommandsPacket) {
						$knownCommands = CommandManager::getCommands();
						foreach ($packet->commandData as $key => $commandData) {
							//Make sure the command is actually from EasyEdit (yea users sometimes run multiple world editors...)
							if (isset($knownCommands[strtolower($commandData->getName())]) && $commandData->getDescription() === $knownCommands[strtolower($commandData->getName())]->getDescription()) {
								$packet->commandData[$key]->overloads = $knownCommands[strtolower($commandData->getName())]->getCommandOverloads();
							}
						}
					}
				}
			}, EventPriority::HIGHEST, EasyEdit::getInstance(), true);
		} catch (Throwable $e) {
			EasyEdit::getInstance()->getLogger()->logException($e);
		}
	}
}