<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;

class CylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/cylinder", "Set a cylinder", "easyedit.command.set", ["/cy"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (($args[2] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$pattern = PatternParser::parseInput($args[2], $player);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(Cylinder::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (int) $args[0], (int) $args[1]), $pattern, $player->getPosition());
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT),
				CommandParameter::standard("height", AvailableCommandsPacket::ARG_TYPE_INT),
				CommandParameter::standard("pattern", AvailableCommandsPacket::ARG_TYPE_RAWTEXT),
			]
		];
	}
}