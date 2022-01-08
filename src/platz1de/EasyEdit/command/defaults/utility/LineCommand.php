<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\task\editing\LineTask;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class LineCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/line", "Draw a line", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE], "//line <x> <y> <z> [pattern]");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (count($args) < 3) {
			$player->sendMessage($this->getUsage());
			return;
		}

		$x = (int) $args[0];
		$y = (int) $args[1];
		$z = (int) $args[2];

		if (isset($args[3])) {
			$block = BlockParser::getBlock($args[3]);
		} else {
			$block = VanillaBlocks::CONCRETE()->setColor(DyeColor::RED());
		}

		LineTask::queue($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), new Vector3($x, $y, $z), StaticBlock::fromBlock($block));
	}

	public function getCompactHelp(): string
	{
		return "//line <x> <y> <z> - Draw a direct line to given position";
	}
}