<?php

namespace platz1de\EasyEdit\command\defaults\history;

use platz1de\EasyEdit\cache\HistoryCache;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\utils\ConfigManager;
use pocketmine\player\Player;

class UndoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/undo", [KnownPermissions::PERMISSION_HISTORY, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (isset($args[0]) && !is_numeric($args[0]) && ConfigManager::isAllowingOtherHistory() && $player->hasPermission(KnownPermissions::PERMISSION_HISTORY_OTHER)) {
			$target = $args[0];
			array_shift($args);
		} else {
			$target = $player->getName();
		}

		if (!HistoryCache::canUndo($target)) {
			Messages::send($player, "no-history");
		}

		$count = min(100, (int) ($args[0] ?? 1));

		for ($i = 0; $i < $count; $i++) {
			HistoryCache::undoStep($target, $player->getName());
		}
	}
}