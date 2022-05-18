<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class HollowCylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/hcylinder", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/hcy", "/hollowcylinder"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 3, $this);
		try {
			$pattern = new SidesPattern((float) ($args[3] ?? 1.0), [PatternParser::parseInput($args[2], $player)]);
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}

		SetTask::queue(Cylinder::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (float) $args[0], (int) $args[1]), $pattern, $player->getPosition());
	}
}