<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\cache\HistoryCache;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;

class RedoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/redo", "Revert your latest undo", "easyedit.command.redo");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (!HistoryCache::canRedo($player->getName())) {
			Messages::send($player, "no-future");
		}

		$count = min(100, (int) ($args[0] ?? 1));

		for ($i = 0; $i < $count; $i++) {
			HistoryCache::redoStep($player->getName());
		}
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::standard("amount", AvailableCommandsPacket::ARG_TYPE_INT, 0, true)
			]
		];
	}
}