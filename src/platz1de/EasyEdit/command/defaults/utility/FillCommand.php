<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\BlockCommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FacingCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\expanding\FillTask;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\block\VanillaBlocks;

class FillCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/fill", [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE]);
		$this->flagOrder = ["block" => false, "direction" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runSettingTask(new FillTask($session->asPlayer()->getWorld()->getFolderName(), BlockVector::fromVector($session->asPlayer()->getPosition()), $flags->getIntFlag("direction"), $flags->getStaticBlockFlag("block")));
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