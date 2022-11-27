<?php

namespace platz1de\EasyEdit\command\defaults\movement;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;

class UnstuckCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/unstuck", [KnownPermissions::PERMISSION_UTIL], ["/u"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		$player->teleport($player->getWorld()->getSafeSpawn($player->getPosition()));
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