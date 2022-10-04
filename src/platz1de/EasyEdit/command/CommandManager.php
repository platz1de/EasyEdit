<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\command\exception\CommandException;
use platz1de\EasyEdit\session\SessionManager;
use pocketmine\player\Player;
use pocketmine\Server;

class CommandManager
{
	/**
	 * @var EasyEditCommand[]
	 */
	private static array $commands = [];

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
		try {
			$session = SessionManager::get($player);
			$command->process($session, CommandFlagManager::parseFlags($command, $args, $session));
		} catch (CommandException $e) {
			$e->sendWarning(SessionManager::get($player));
		}
	}

	/**
	 * @return EasyEditCommand[]
	 */
	public static function getCommands(): array
	{
		return self::$commands;
	}

	/**
	 * @param string $command
	 * @return EasyEditCommand|null
	 */
	public static function getKnownCommand(string $command): ?EasyEditCommand
	{
		foreach (self::$commands as $cmd) {
			if (strtolower($cmd->getName()) === strtolower($command)) {
				return $cmd;
			}
			foreach ($cmd->getAliases() as $alias) {
				if (strtolower($alias) === strtolower($command)) {
					return $cmd;
				}
			}
		}
		return null;
	}
}