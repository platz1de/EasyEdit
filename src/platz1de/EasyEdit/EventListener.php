<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\SelectionManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Axe;
use pocketmine\item\Shovel;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\player\Player;

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
		if ($axe instanceof Axe && $axe->getTier() === ToolTier::WOOD() && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission("easyedit.position")) {
			$event->cancel();
			Cube::selectPos1($event->getPlayer(), $event->getBlock()->getPos());
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
		if ($item instanceof TieredTool && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $item->getTier() === ToolTier::WOOD() && $event->getPlayer()->isCreative()) {
			if ($item instanceof Axe && $event->getPlayer()->hasPermission("easyedit.position")) {
				$event->cancel();
				Cube::selectPos2($event->getPlayer(), $event->getBlock()->getPos());
			} elseif ($item instanceof Shovel && $event->getPlayer()->hasPermission("easyedit.brush")) {
				$event->cancel();
				BrushHandler::handleBrush($item->getNamedTag(), $event->getPlayer());
			}
		}
	}

	public function onLevelChange(EntityTeleportEvent $event): void
	{
		//TODO: Replace this with proper differentiation of player and selection level
		$player = $event->getEntity();
		if ($player instanceof Player && $event->getFrom()->getWorld() !== $event->getTo()->getWorld()) {
			SelectionManager::clearForPlayer($player->getName());
		}
	}
}