<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FacingCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\result\SelectionManipulationResult;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\DynamicStoredFlipTask;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Facing;

class FlipCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/flip", [KnownPermissions::PERMISSION_CLIPBOARD]);
		$this->flagOrder = ["direction" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new DynamicStoredFlipTask($session->getClipboard(), Facing::axis($flags->getIntFlag("direction"))))->then(function (SelectionManipulationResult $result) use ($session) {
			$session->sendMessage("blocks-rotated", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getChanged())]);
		});
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"direction" => FacingCommandFlag::default(VectorUtils::getFacing($session->asPlayer()->getLocation()), "direction", ["dir"], "d")
		];
	}
}