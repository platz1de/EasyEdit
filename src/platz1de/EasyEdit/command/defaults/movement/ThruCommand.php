<?php

namespace platz1de\EasyEdit\command\defaults\movement;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use pocketmine\block\Air;
use pocketmine\math\VoxelRayTrace;

class ThruCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/thru", [KnownPermissions::PERMISSION_UTIL], ["/t"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		$ignore = true;
		foreach (VoxelRayTrace::inDirection($player->getEyePos(), $player->getDirectionVector(), 10) as $vector) {
			if ($ignore && $player->getWorld()->getBlock($vector) instanceof Air) {
				continue;
			}
			$ignore = false;
			if ($player->getWorld()->getBlock($vector) instanceof Air && $player->getWorld()->getBlock($vector->down()) instanceof Air) {
				$player->teleport($vector->down());
				break;
			}
		}
	}

	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from [];
	}
}