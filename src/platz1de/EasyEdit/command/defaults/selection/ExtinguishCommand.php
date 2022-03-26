<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;

class ExtinguishCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/extinguish", [KnownPermissions::PERMISSION_EDIT],  ["/ext"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (isset($args[0])) {
			$selection = Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (float) $args[0]);
		} else {
			$selection = ArgumentParser::getSelection($player);
		}

		SetTask::queue($selection, BlockPattern::from([StaticBlock::fromBlock(VanillaBlocks::AIR())], PatternArgumentData::create()->setBlock(DynamicBlock::fromBlock(VanillaBlocks::FIRE()))), $player->getPosition());
	}
}