<?php

namespace platz1de\EasyEdit\command;

use Generator;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

abstract class EasyEditCommand extends Command implements PluginOwned
{
	/**
	 * @param string   $name
	 * @param string[] $permissions
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $permissions, array $aliases = [])
	{
		$realName = str_starts_with($name, "/") ? substr($name, 1) : $name;
		parent::__construct($name, Messages::translate("command-$realName-description"), $this->prepareUsage($realName), $aliases);
		$this->setPermissions($permissions);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 * @return void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		if (!$sender instanceof Player) {
			return;
		}
		if (!$this->testPermission($sender)) {
			return;
		}

		CommandManager::processCommand($this, $args, $sender);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	abstract public function process(Session $session, CommandFlagCollection $flags): void;

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	abstract public function getKnownFlags(Session $session): array;

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	abstract public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator;

	/**
	 * @return EasyEdit
	 */
	public function getOwningPlugin(): Plugin
	{
		return EasyEdit::getInstance();
	}

	/**
	 * @param string $commandName
	 * @return string
	 */
	public function prepareUsage(string $commandName): string
	{
		$usages = [];
		foreach (explode(PHP_EOL, Messages::translate("command-$commandName-usage")) as $help) {
			$usages[] = str_contains($help, " - ") ? $help : $help . " - " . Messages::translate("command-$commandName-description");
		}
		return implode(PHP_EOL, $usages);
	}
}