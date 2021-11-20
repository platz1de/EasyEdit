<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\pattern\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use pocketmine\player\Player;

class HollowCylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/hcylinder", "Set a hollow cylinder", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], "//hcylinder <radius> <height> <pattern> [thickness]", ["/hcy", "/hollowcylinder"]);
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
			$pattern = new Pattern([new SidesPattern(PatternParser::parseInputArgument($args[2], $player), PatternArgumentData::create()->setFloat("thickness", (float) ($args[3] ?? 1.0)))]);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		SetTask::queue(Cylinder::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (float) $args[0], (int) $args[1]), $pattern, $player->getPosition());
	}
}