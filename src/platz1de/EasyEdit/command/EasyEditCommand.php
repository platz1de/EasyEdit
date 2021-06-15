<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\EasyEdit;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

abstract class EasyEditCommand extends Command implements PluginIdentifiableCommand
{
	public function __construct(string $name, string $description, string $permission, string $usage = null, array $aliases = [])
	{
		if ($usage === null) {
			$usage = "/" . $name;
		}
		parent::__construct($name, $description, $usage, $aliases);
		$this->setPermission($permission);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 * @return void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		if (!$sender instanceof Player || !$this->testPermission($sender)) {
			return;
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
	public function getPlugin(): Plugin
	{
		return EasyEdit::getInstance();
	}
}