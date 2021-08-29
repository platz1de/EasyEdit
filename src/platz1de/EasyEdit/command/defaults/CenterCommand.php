<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use Throwable;

class CenterCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/center", "Set the center Blocks (1-8)", "easyedit.command.set", "//center [block]", ["/middle"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			//TODO: Fix parsing
			//$block = PatternParser::getBlock($args[0]);
			$block = VanillaBlocks::STONE();
		} catch (ParseError) {
			$block = VanillaBlocks::BEDROCK();
		}

		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			Selection::validate($selection, Cube::class);
		} catch (Throwable) {
			Messages::send($player, "no-selection");
			return;
		}

		//Move this somewhere else?
		$xPos = ($selection->getPos1()->getX() + $selection->getPos2()->getX()) / 2;
		$yPos = ($selection->getPos1()->getY() + $selection->getPos2()->getY()) / 2;
		$zPos = ($selection->getPos1()->getZ() + $selection->getPos2()->getZ()) / 2;
		$world = $selection->getWorld();

		for ($x = floor($xPos); $x <= ceil($xPos); $x++) {
			for ($y = floor($yPos); $y <= ceil($yPos); $y++) {
				for ($z = floor($zPos); $z <= ceil($zPos); $z++) {
					$world->setBlock(new Vector3($x, $y, $z), $block, false);
				}
			}
		}
	}
}