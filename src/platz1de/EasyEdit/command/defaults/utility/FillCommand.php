<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\task\editing\FillTask;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\player\Player;

class FillCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/fill", "Fill an area", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE], "//fill <Block>");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (count($args) < 1) {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$block = StaticBlock::fromBlock(BlockParser::getBlock($args[0]));
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}
		FillTask::queue($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition()->asVector3(), VectorUtils::getFacing($player->getLocation()), $block);
	}
}