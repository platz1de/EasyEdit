<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

abstract class EasyEditCommand extends Command implements PluginOwned
{
	/**
	 * @var string[]
	 */
	private array $permissions;

	/**
	 * @param string   $name
	 * @param string[] $permissions
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $permissions, array $aliases = [])
	{
		$realName = str_starts_with($name, "/") ? substr($name, 1) : $name;
		parent::__construct($name, Messages::translate("command-$realName-description"), $this->prepareUsage($realName), $aliases);
		$this->permissions = $permissions;
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
		foreach ($this->permissions as $permission) {
			if (!$this->testPermission($sender, $permission)) {
				return;
			}
		}

		CommandManager::processCommand($this, $args, $sender);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	abstract public function process(Player $player, array $args): void;

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
			$usages[] = str_contains($help, "-") ? $help : $help . " - " . Messages::translate("command-$commandName-description");
		}
		return implode(PHP_EOL, $usages);
	}
}