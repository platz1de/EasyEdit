<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\pattern\PatternParser;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;

class HollowSphereCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/hsphere", "Set a hollow sphere", "easyedit.command.set", ["/hsph", "/hollowsphere"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (($args[1] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$pattern = new Pattern([new SidesPattern(PatternParser::parseInputArgument($args[1], $player), PatternArgumentData::create()->setFloat("thickness", (float) ($args[2] ?? 1.0)))]);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (float) $args[0]), $pattern, $player->getPosition());
	}


	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT),
				CommandParameter::standard("pattern", AvailableCommandsPacket::ARG_TYPE_RAWTEXT),
				CommandParameter::standard("thickness", AvailableCommandsPacket::ARG_TYPE_FLOAT, 0, true)
			]
		];
	}
}