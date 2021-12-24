<?php

namespace platz1de\EasyEdit\command;

use platz1de\EasyEdit\EasyEdit;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use UnexpectedValueException;

abstract class EasyEditCommand extends Command implements PluginOwned
{
	/**
	 * @var string[]
	 */
	private array $permissions;

	/**
	 * @param string      $name
	 * @param string      $description
	 * @param string[]    $permissions
	 * @param string|null $usage
	 * @param string[]    $aliases
	 */
	public function __construct(string $name, string $description, array $permissions, string $usage = null, array $aliases = [])
	{
		if ($usage === null) {
			$usage = "/" . $name;
		}
		parent::__construct($name, $description, $usage, $aliases);
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
	 * @return string
	 */
	public function getCompactHelp(): string
	{
		if ($this->getUsage() instanceof Translatable || $this->getDescription() instanceof Translatable) {
			throw new UnexpectedValueException("EasyEdit commands should not use translatable usages or descriptions");
		}
		return $this->getUsage() . " - " . $this->getDescription();
	}
}