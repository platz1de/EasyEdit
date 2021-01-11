<?php

namespace platz1de\EasyEdit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

abstract class EasyEditCommand extends Command
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
	 * @param array         $args
	 * @return mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof Player || !$this->testPermission($sender)) {
			return;
		}

		CommandManager::processCommand($this, $args, $sender);
	}

	abstract public function process(Player $player, array $args, array $flags);
}