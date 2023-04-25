<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\SetTask;
use pocketmine\block\VanillaBlocks;

class ExtinguishCommand extends SphericalSelectionCommand
{
	public function __construct()
	{
		parent::__construct("/extinguish", [KnownPermissions::PERMISSION_EDIT], ["/ext"]);
	}

	/**
	 * @param Session   $session
	 * @param Selection $selection
	 * @return void
	 */
	public function processSelection(Session $session, Selection $selection): void
	{
		$session->runSettingTask(new SetTask($selection, new BlockPattern(DynamicBlock::from(VanillaBlocks::FIRE()), [StaticBlock::from(VanillaBlocks::AIR())])));
	}
}