<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\utils\HighlightingManager;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Axe;
use pocketmine\item\Shovel;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

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
			Cube::selectPos1($event->getPlayer(), $event->getBlock()->getPosition());
		}
	}

	public function onUse(PlayerItemUseEvent $event): void
	{
		if (self::$cooldown < microtime(true)) {
			self::$cooldown = microtime(true) + 0.5;
		} else {
			return;
		}

		$item = $event->getItem();
		if ($item instanceof TieredTool && $item->getTier() === ToolTier::WOOD() && $event->getPlayer()->isCreative()) {
			if ($item instanceof Axe && $event->getPlayer()->hasPermission("easyedit.position")) {
				$event->cancel();
				$target = $event->getPlayer()->getTargetBlock(100);
				if ($target instanceof Block) {
					Cube::selectPos2($event->getPlayer(), $target->getPosition());
				}
			} elseif ($item instanceof Shovel && $event->getPlayer()->hasPermission("easyedit.brush")) {
				$event->cancel();
				BrushHandler::handleBrush($item->getNamedTag(), $event->getPlayer());
			}
		}
	}

	public function onLevelChange(EntityTeleportEvent $event): void
	{
		$player = $event->getEntity();
		if ($player instanceof Player) {
			$playerName = $player->getName();
			EasyEdit::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($playerName): void {
				HighlightingManager::resendAll($playerName);
			}));
		}
	}
}