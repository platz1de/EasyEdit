<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\task\editing\DrainTask;
use platz1de\EasyEdit\task\editing\FillTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\player\Player;

class DrainCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/drain", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		DrainTask::queue($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition()->asVector3());
	}
}