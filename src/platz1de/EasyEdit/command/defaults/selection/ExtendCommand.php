<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class ExtendCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/extend", "Extend the selected Area", [KnownPermissions::PERMISSION_SELECT], "//extend [count]\n//extend vertical", ["/expand"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$selection = ArgumentParser::getCube($player);
		$pos1 = $selection->getPos1();
		$pos2 = $selection->getPos2();

		if (($args[0] ?? "") === "vert" || ($args[0] ?? "") === "vertical") {
			$pos1 = new Vector3($pos1->getX(), World::Y_MIN, $pos1->getZ());
			$pos2 = new Vector3($pos2->getX(), World::Y_MAX - 1, $pos2->getZ());
		} else {
			$count = (int) ($args[0] ?? 1);
			$offset = VectorUtils::moveVectorInSight($player->getLocation(), Vector3::zero(), $count);
			if ($count < 0 xor $offset->abs()->equals($offset)) {
				$pos2 = $pos2->addVector($offset);
			} else {
				$pos1 = $pos1->addVector($offset);
			}
		}

		$selection->setPos1($pos1);
		$selection->setPos2($pos2);
	}

	public function getCompactHelp(): string
	{
		return "//extend [count] - Extend the selected Area\n//extend vertical - Fully extend the selected Area vertically";
	}
}