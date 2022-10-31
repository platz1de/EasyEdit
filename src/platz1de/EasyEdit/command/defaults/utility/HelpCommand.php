<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use Generator;
use platz1de\EasyEdit\command\CommandManager;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\session\Session;
use pocketmine\lang\Translatable;
use UnexpectedValueException;

class HelpCommand extends EasyEditCommand
{
	private const COMMANDS_PER_PAGE = 8;

	public function __construct()
	{
		parent::__construct("/commands", [], ["/h", "/cmd"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$commands = [];
		foreach (CommandManager::getCommands() as $command) {
			$usage = $command->getUsage();
			if ($usage instanceof Translatable) {
				throw new UnexpectedValueException("EasyEdit commands shouldn't contain translatable data");
			}
			foreach (explode(PHP_EOL, $usage) as $help) {
				$commands[] = $help;
			}
		}
		$page = max(1, $flags->getIntFlag("page"));
		if (($page - 1) * self::COMMANDS_PER_PAGE >= count($commands)) {
			$page = (int) ceil(count($commands) / self::COMMANDS_PER_PAGE);
		}
		$show = array_slice($commands, ($page - 1) * self::COMMANDS_PER_PAGE, self::COMMANDS_PER_PAGE);
		$session->sendMessage("command-list", ["{commands}" => implode("\n", $show), "{start}" => (string) (($page - 1) * self::COMMANDS_PER_PAGE + 1), "{end}" => (string) (($page - 1) * self::COMMANDS_PER_PAGE + count($show)), "{total}" => (string) count($commands)]);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"page" => new IntegerCommandFlag("page", [], "p")
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
		if (!$flags->hasFlag("page")) {
			yield IntegerCommandFlag::with((int) ($args[0] ?? 1), "page");
		}
	}
}