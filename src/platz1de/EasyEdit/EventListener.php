<?php

namespace platz1de\EasyEdit;

use pocketmine\block\Air;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Axe;
use pocketmine\item\TieredTool;

class EventListener implements Listener
{
	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event): void
	{
		$axe = $event->getItem();
		if ($axe instanceof Axe && $axe->getTier() === TieredTool::TIER_WOODEN && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission("easyedit.position")) {
			$event->setCancelled();
			EasyEdit::selectPos1($event->getPlayer(), $event->getBlock()->asVector3());
		}
	}

	public function onInteract(PlayerInteractEvent $event): void
	{
		$axe = $event->getItem();
		if($axe instanceof Axe && !$event->getBlock() instanceof Air && $axe->getTier() === TieredTool::TIER_WOODEN && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission("easyedit.position")){
			$event->setCancelled();
			EasyEdit::selectPos2($event->getPlayer(), $event->getBlock()->asVector3());
		}
	}
}