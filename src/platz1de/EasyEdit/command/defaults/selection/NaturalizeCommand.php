<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use pocketmine\block\VanillaBlocks;

class NaturalizeCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize", ["top" => false, "middle" => false, "bottom" => false], ["/nat"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 * @return Pattern
	 */
	public function parsePattern(Session $session, CommandFlagCollection $flags): Pattern
	{
		return new NaturalizePattern($flags->getPatternFlag("top"), $flags->getPatternFlag("middle"), $flags->getPatternFlag("bottom"));
	}

	public function getKnownFlags(Session $session): array
	{
		return [
			"top" => PatternCommandFlag::default(StaticBlock::from(VanillaBlocks::GRASS()), "top", [], "t"),
			"middle" => PatternCommandFlag::default(StaticBlock::from(VanillaBlocks::DIRT()), "middle", [], "m"),
			"bottom" => PatternCommandFlag::default(StaticBlock::from(VanillaBlocks::STONE()), "bottom", [], "b"),
		];
	}
}