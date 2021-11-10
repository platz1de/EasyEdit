<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\EasyEdit;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

abstract class EasyEditCommand extends Command implements PluginOwned
{
	public function __construct(string $name, string $description, string $permission, array $aliases = [])
	{
		$overloads = $this->getCommandOverloads();
		$parts = [];
		foreach ($overloads as $overload) {
			$parts[] = "/" . $name . " " . implode(" ", array_map(function (CommandParameter $parameter): string {
					return $parameter->paramName;
				}, $overload));
		}
		parent::__construct($name, $description, implode("\n", $parts), $aliases);
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
	 * @return CommandParameter[][]
	 */
	abstract public function getCommandOverloads(): array;

	/**
	 * @return EasyEdit
	 */
	public function getOwningPlugin(): Plugin
	{
		return EasyEdit::getInstance();
	}
}