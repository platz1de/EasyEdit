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
	 * @param string[]        $args
	 * @param Player          $player
	 */
	public static function processCommand(EasyEditCommand $command, array $args, Player $player): void
	{
		//TODO: Flags?
		$command->process($player, $args);
	}
}