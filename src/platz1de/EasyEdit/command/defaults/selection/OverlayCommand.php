<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\BlockCommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\pattern\block\BlockGroup;
use platz1de\EasyEdit\pattern\logic\relation\AbovePattern;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\world\HeightMapCache;

class OverlayCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/overlay");
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 * @return Pattern
	 */
	public function parsePattern(Session $session, CommandFlagCollection $flags): Pattern
	{
		return new BlockPattern(new BlockGroup(HeightMapCache::getIgnore()), [new AbovePattern($flags->getBlockFlag("block"), [$flags->getPatternFlag("pattern")])]);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"pattern" => new PatternCommandFlag("pattern", [], "p"),
			"block" => BlockCommandFlag::default(BlockGroup::inverted(HeightMapCache::getIgnore()), "block", [], "b"),
		];
	}
}