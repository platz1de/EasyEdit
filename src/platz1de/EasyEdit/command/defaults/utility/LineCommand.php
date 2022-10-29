<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use Generator;
use InvalidArgumentException;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\flags\CommandArgumentFlag;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\SetValueCommandFlag;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\LineTask;
use platz1de\EasyEdit\task\pathfinding\PathfindingTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use platz1de\EasyEdit\utils\BlockParser;

class LineCommand extends SimpleFlagArgumentCommand
{
	private const MODE_LINE = 0;
	private const MODE_PATH = 1;
	private const MODE_SOLID_PATH = 2;

	public function __construct()
	{
		parent::__construct("/line", ["x" => true, "y" => true, "z" => true], [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$target = ArgumentParser::parseCoordinates($session, $flags->getStringFlag("x"), $flags->getStringFlag("y"), $flags->getStringFlag("z"));

		try {
			$block = BlockParser::parseBlockIdentifier($flags->getStringFlag("block"));
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}

		switch ($flags->getIntFlag("mode")) {
			case self::MODE_LINE:
				$session->runTask(new LineTask($session->asPlayer()->getPosition(), $target, new StaticBlock($block)));
				break;
			case self::MODE_PATH:
				$session->runTask(new PathfindingTask($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), $target, true, new StaticBlock($block)));
				break;
			case self::MODE_SOLID_PATH:
				$session->runTask(new PathfindingTask($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), $target, false, new StaticBlock($block)));
				break;
			default:
				throw new InvalidArgumentException("Invalid line mode");
		}
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		//TODO: Turn these into integers (handle special cases like ~ and ^ while parsing flags)
		return [
			"x" => new StringCommandFlag("x"),
			"y" => new StringCommandFlag("y"),
			"z" => new StringCommandFlag("z"),
			"block" => new StringCommandFlag("block"),
			"pathfind" => new SetValueCommandFlag("mode", self::MODE_PATH, ["find", "search"], "f"),
			"find-line" => new SetValueCommandFlag("mode", self::MODE_SOLID_PATH, ["solid", "no-diagonal", "find-direct"], "s"),
		];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (isset($args[0]) && !is_numeric($args[0])) {
			if (!$flags->hasFlag("mode")) {
				yield new SetValueCommandFlag("mode", match ($args[0]) {
					"find", "search" => self::MODE_PATH,
					"solid", "no-diagonal", "find-direct", "find-line" => self::MODE_SOLID_PATH,
					default => self::MODE_LINE
				});
			}
			array_shift($args);
		}
		if (!$flags->hasFlag("block")) {
			yield new CommandArgumentFlag("block", $args[3] ?? "red_concrete");
		}
		parent::parseArguments($flags, $session, $args);
	}
}