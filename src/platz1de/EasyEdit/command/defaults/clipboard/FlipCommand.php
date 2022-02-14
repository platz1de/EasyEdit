<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\task\DynamicStoredFlipTask;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use Throwable;

class FlipCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/flip", "Flip the Clipboard", [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$selection = ClipBoardManager::getFromPlayer($player->getName());
		} catch (Throwable) {
			Messages::send($player, "no-clipboard");
			return;
		}
		DynamicStoredFlipTask::queue($player->getName(), $selection, Facing::axis(VectorUtils::getFacing($player->getLocation())));
	}
}