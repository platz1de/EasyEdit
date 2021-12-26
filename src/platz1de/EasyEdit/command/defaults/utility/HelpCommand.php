<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\CommandManager;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use pocketmine\player\Player;

class HelpCommand extends EasyEditCommand
{
	private const COMMANDS_PER_PAGE = 8;

	public function __construct()
	{
		parent::__construct("/commands", "List all EasyEdit commands", [], "//help [page]", ["/h", "/cmd"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$page = isset($args[0]) ? (int) $args[0] : 1;
		$commands = CommandManager::getCommands();
		if ($page < 1) {
			$page = 1;
		}
		if (($page - 1) * self::COMMANDS_PER_PAGE >= count($commands)) {
			$page = (int) ceil(count($commands) / self::COMMANDS_PER_PAGE);
		}
		$show = array_slice($commands, ($page - 1) * self::COMMANDS_PER_PAGE, self::COMMANDS_PER_PAGE);
		Messages::send($player, "command-list", ["{commands}" => implode("\n", array_map(static function (EasyEditCommand $command): string {
			return $command->getCompactHelp();
		}, $show)), "{start}" => (string) (($page - 1) * self::COMMANDS_PER_PAGE + 1), "{end}" => (string) (($page - 1) * self::COMMANDS_PER_PAGE + count($show)), "{total}" => (string) count($commands)]);
	}
}