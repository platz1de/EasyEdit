<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class ExtendCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/extend", [KnownPermissions::PERMISSION_SELECT],  ["/expand"]);
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
			$selection->setPos1(new Vector3($pos1->getX(), World::Y_MIN, $pos1->getZ()));
			$selection->setPos2(new Vector3($pos2->getX(), World::Y_MAX - 1, $pos2->getZ()));
			return;
		}

		$offset = ArgumentParser::parseDirectionVector($player, $args[0] ?? null, $args[1] ?? null, $count);
		if ($count < 0 xor $offset->abs()->equals($offset)) {
			$selection->setPos1($pos1);
			$selection->setPos2($pos2->addVector($offset));
		} else {
			$selection->setPos2($pos2);
			$selection->setPos1($pos1->addVector($offset));
		}
	}
}