<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\flags\BlockCommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FacingCommandFlag;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\expanding\FillTask;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\block\VanillaBlocks;

class FillCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/fill", ["block" => false, "direction" => false], [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new FillTask($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition()->asVector3(), $flags->getIntFlag("direction"), $flags->getStaticBlockFlag("block")));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"block" => BlockCommandFlag::default(StaticBlock::from(VanillaBlocks::WATER()), "block", [], "b"),
			"direction" => FacingCommandFlag::default(VectorUtils::getFacing($session->asPlayer()->getLocation()), "direction", ["dir"], "d")
		];
	}
}