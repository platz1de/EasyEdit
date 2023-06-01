<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use Generator;
use platz1de\EasyEdit\command\flags\BlockCommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\pattern\block\MaskedBlockGroup;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\world\HeightMapCache;

class ReplaceCommand extends AliasedPatternCommand
{
	public function __construct()
	{
		parent::__construct("/replace");
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 * @return Pattern
	 */
	public function parsePattern(Session $session, CommandFlagCollection $flags): Pattern
	{
		return new BlockPattern($flags->getBlockFlag("block"), [$flags->getPatternFlag("pattern")]);
	}

	public function getKnownFlags(Session $session): array
	{
		return [
			"block" => new BlockCommandFlag("block", [], "b"),
			"pattern" => new PatternCommandFlag("pattern", [], "p"),
		];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (count($args) >= 2) {
			yield (new BlockCommandFlag("block"))->parseArgument($this, $session, $args[0]);
			array_shift($args);
		} else {
			yield BlockCommandFlag::with(new MaskedBlockGroup(HeightMapCache::getIgnore()), "block");
		}
		yield (new PatternCommandFlag("pattern"))->parseArgument($this, $session, $args[0]);
	}
}