<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\SelectionManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Axe;
use pocketmine\item\Shovel;
use pocketmine\item\TieredTool;
use pocketmine\Player;

class EventListener implements Listener
{
	//don't spam everything
	private static $cooldown = 0;

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event): void
	{
		$axe = $event->getItem();
		if ($axe instanceof Axe && $axe->getTier() === TieredTool::TIER_WOODEN && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission("easyedit.position")) {
			$event->setCancelled();
			Cube::selectPos1($event->getPlayer(), $event->getBlock()->asVector3());
		}
	}

	public function onInteract(PlayerInteractEvent $event): void
	{
		if (self::$cooldown < microtime(true)) {
			self::$cooldown = microtime(true) + 0.5;
		} else {
			return;
		}

		$axe = $event->getItem();
		if ($axe instanceof Axe && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $axe->getTier() === TieredTool::TIER_WOODEN && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission("easyedit.position")) {
			$event->setCancelled();
			Cube::selectPos2($event->getPlayer(), $event->getBlock()->asVector3());
		} elseif ($axe instanceof Shovel && $axe->getTier() === TieredTool::TIER_WOODEN && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission("easyedit.brush")) {
			$event->setCancelled();
			BrushHandler::handleBrush($axe->getNamedTag(), $event->getPlayer());
		}
	}

	public function onLevelChange(EntityLevelChangeEvent $event): void
	{
		//TODO: Replace this with proper differentiation of player and selection level
		if ($event->getEntity() instanceof Player) {
			SelectionManager::clearForPlayer($event->getEntity()->getName());
		}
	}
}