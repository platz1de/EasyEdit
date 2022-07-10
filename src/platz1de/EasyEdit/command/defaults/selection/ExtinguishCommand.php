<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use pocketmine\block\VanillaBlocks;

class ExtinguishCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/extinguish", [KnownPermissions::PERMISSION_EDIT], ["/ext"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		if (isset($args[0])) {
			$selection = Sphere::aroundPoint($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), (float) $args[0]);
		} else {
			$selection = $session->getSelection();
		}

		$session->runTask(SetTask::from($session->asPlayer()->getWorld()->getFolderName(), null, $selection, $session->asPlayer()->getPosition(), new BlockPattern(DynamicBlock::from(VanillaBlocks::FIRE()), [StaticBlock::from(VanillaBlocks::AIR())])));
	}
}