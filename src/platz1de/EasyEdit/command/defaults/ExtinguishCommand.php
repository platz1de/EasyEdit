<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use Throwable;

class ExtinguishCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/extinguish", "Extinguish fire", "easyedit.command.set", "//extinguish [radius]");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (isset($args[0])) {
			$selection = new Sphere($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (int) $args[0]);
		} else {
			try {
				$selection = SelectionManager::getFromPlayer($player->getName());
				Selection::validate($selection);
			} catch (Throwable) {
				Messages::send($player, "no-selection");
				return;
			}
		}

		SetTask::queue($selection, new Pattern([new BlockPattern([StaticBlock::from(VanillaBlocks::AIR())], PatternArgumentData::create()->setBlock(DynamicBlock::from(VanillaBlocks::FIRE())))]), $player->getPosition());
	}
}