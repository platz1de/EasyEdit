<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use Generator;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\flags\CommandArgumentFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\pattern\block\SolidBlock;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

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
		if ($flags->hasFlag("block")) {
			try {
				$block = PatternParser::getBlockType($flags->getStringFlag("block"));
			} catch (ParseError $exception) {
				throw new PatternParseException($exception);
			}
		} else {
			$block = new SolidBlock();
		}
		return new BlockPattern($block, [$flags->getPatternFlag("pattern")]);
	}

	public function getKnownFlags(Session $session): array
	{
		return array_merge(parent::getKnownFlags($session), [
			"block" => new StringCommandFlag("block", [], "b"),
		]);
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (count($args) >= 2) {
			yield new CommandArgumentFlag("block", $args[0]);
			array_shift($args);
		}
		yield from parent::parseArguments($flags, $session, $args);
	}
}