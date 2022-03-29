<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\pattern\block\SolidBlock;
use platz1de\EasyEdit\pattern\logic\NotPattern;
use platz1de\EasyEdit\pattern\logic\relation\AbovePattern;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class OverlayCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/overlay");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 * @return Pattern
	 */
	public function parsePattern(Player $player, array $args): Pattern
	{
		ArgumentParser::requireArgumentCount($args, 1, $this);
		return NotPattern::from([BlockPattern::from([AbovePattern::from([ArgumentParser::parseCombinedPattern($player, $args, 0)], PatternArgumentData::create()->setBlock(SolidBlock::create()))], PatternArgumentData::create()->setBlock(SolidBlock::create()))]);
	}
}