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
	/**
	 * don't spam everything
	 * @var float
	 */
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

		$item = $event->getItem();
		if ($item instanceof TieredTool && $item->getTier() === TieredTool::TIER_WOODEN && $event->getPlayer()->isCreative()) {
			if ($item instanceof Axe && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $event->getPlayer()->hasPermission("easyedit.position")) {
				$event->setCancelled();
				Cube::selectPos2($event->getPlayer(), $event->getBlock()->asVector3());
			} elseif ($item instanceof Shovel && ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR) && $event->getPlayer()->hasPermission("easyedit.brush")) {
				$event->setCancelled();
				BrushHandler::handleBrush($item->getNamedTag(), $event->getPlayer());
			}
		}
	}

	public function onLevelChange(EntityLevelChangeEvent $event): void
	{
		//TODO: Replace this with proper differentiation of player and selection level
		$player = $event->getEntity();
		if ($player instanceof Player) {
			SelectionManager::clearForPlayer($player->getName());
		}
	}
}