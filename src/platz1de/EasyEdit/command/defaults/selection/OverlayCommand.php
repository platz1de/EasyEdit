<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\pattern\block\SolidBlock;
use platz1de\EasyEdit\pattern\logic\NotPattern;
use platz1de\EasyEdit\pattern\logic\relation\AbovePattern;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

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
		return new NotPattern(new BlockPattern(new SolidBlock(), [new AbovePattern(new SolidBlock(), [$flags->getPatternFlag("pattern")])]));
	}
}