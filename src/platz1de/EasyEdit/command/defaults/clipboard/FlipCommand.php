<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\task\DynamicStoredFlipTask;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use Throwable;
use UnexpectedValueException;

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

		$vector = VectorUtils::moveVectorInSight($player->getLocation(), new Vector3(0, 0, 0));
		$axis = match ($vector->getX() . ":" . $vector->getY() . ":" . $vector->getZ()) {
			"1:0:0", "-1:0:0" => Axis::X,
			"0:1:0", "0:-1:0" => Axis::Y,
			"0:0:1", "0:0:-1" => Axis::Z,
			default => throw new UnexpectedValueException("Unknown Axis " . $vector->getX() . ":" . $vector->getY() . ":" . $vector->getZ())
		};

		DynamicStoredFlipTask::queue($player->getName(), $selection, $axis);
	}
}