<?php

namespace platz1de\EasyEdit\command\defaults\movement;

use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\session\Session;
use pocketmine\block\Air;
use pocketmine\block\VanillaBlocks;

class UpCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/up", ["amount" => false], [KnownPermissions::PERMISSION_UTIL, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		$player->teleport($player->getPosition()->up($flags->getIntFlag("amount")));
		if ($player->getWorld()->getBlock($player->getPosition()->floor()->down()) instanceof Air) {
			$player->getWorld()->setBlock($player->getPosition()->floor()->down(), VanillaBlocks::GLASS());
		}
	}

	public function getKnownFlags(Session $session): array
	{
		return [
			"amount" => IntegerCommandFlag::default(1, "amount", ["count"], "a")
		];
	}
}