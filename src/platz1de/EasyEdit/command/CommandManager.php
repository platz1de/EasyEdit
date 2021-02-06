<?php

namespace platz1de\EasyEdit\command;

use pocketmine\Player;
use pocketmine\Server;

class CommandManager
{
	/**
	 * @var array
	 */
	private static $commands = [];

	/**
	 * @param EasyEditCommand $command
	 */
	public static function registerCommand(EasyEditCommand $command): void
	{
		self::$commands[strtolower($command->getName())] = $command;
		Server::getInstance()->getCommandMap()->register("easyedit", $command);
	}

	/**
	 * @param EasyEditCommand[] $commands
	 */
	public static function registerCommands(array $commands): void
	{
		foreach ($commands as $command) {
			self::registerCommand($command);
		}
	}

	/**
	 * @param EasyEditCommand $command
	 * @param array           $args
	 * @param Player          $player
	 */
	public static function processCommand(EasyEditCommand $command, array $args, Player $player): void
	{
		$realArgs = [];
		$flags = [];
		$flag = null;
		foreach ($args as $arg) {
			if ($flag !== null) {
				$flags[$flag] = $arg;
				$flag = null;
			} elseif (strpos($arg, "-") === 0 && !is_numeric(substr($arg, 1))) {
				$flag = substr($arg, 1);
			} else {
				$realArgs[] = $arg;
			}
		}

		$command->process($player, $realArgs, $flags);
	}
}