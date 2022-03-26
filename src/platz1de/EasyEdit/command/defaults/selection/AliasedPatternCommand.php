<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

abstract class AliasedPatternCommand extends EasyEditCommand
{
	/**
	 * @param string   $name
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $aliases = [])
	{
		parent::__construct($name, [KnownPermissions::PERMISSION_EDIT], $aliases);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		SetTask::queue(ArgumentParser::getSelection($player), $this->parsePattern($player, $args), $player->getPosition());
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 * @return Pattern
	 */
	abstract public function parsePattern(Player $player, array $args): Pattern;
}