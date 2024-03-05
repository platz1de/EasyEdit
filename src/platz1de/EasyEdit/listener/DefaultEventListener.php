<?php

namespace platz1de\EasyEdit\listener;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\expanding\ExtendBlockFaceTask;
use platz1de\EasyEdit\utils\BlockInfoTool;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\world\clientblock\ClientSideBlockManager;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Axe;
use pocketmine\item\BlazeRod;
use pocketmine\item\Shovel;
use pocketmine\item\Stick;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use Throwable;

class DefaultEventListener implements Listener
{
	use ToggleableEventListener;

	/**
	 * @var array<string, float>
	 */
	private static array $cooldown = [];
	private const CREATIVE_REACH = 5;

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event): void
	{
		$axe = $event->getItem();
		if ($axe instanceof Axe && $axe->getTier() === ToolTier::WOOD() && $event->getPlayer()->isCreative() && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_SELECT)) {
			$event->cancel();
			SessionManager::get($event->getPlayer())->selectPos1($event->getBlock()->getPosition());
		} elseif ($axe instanceof Stick && $axe->getNamedTag()->getByte("isInfoStick", 0) === 1) {
			$event->cancel();
			BlockInfoTool::display(SessionManager::get($event->getPlayer()), $event->getBlock());
		}

		self::$cooldown[$event->getPlayer()->getUniqueId()->getBytes()] = microtime(true) + ConfigManager::getToolCooldown();
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event): void
	{
		if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			$canEdit = (self::$cooldown[$event->getPlayer()->getUniqueId()->getBytes()] ?? .0) < microtime(true);
			if ($canEdit) {
				self::$cooldown[$event->getPlayer()->getUniqueId()->getBytes()] = microtime(true) + ConfigManager::getToolCooldown();
			}

			$item = $event->getItem();
			if ($item instanceof TieredTool && $item->getTier() === ToolTier::WOOD() && $event->getPlayer()->isCreative()) {
				if ($item instanceof Axe && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_SELECT)) {
					$event->cancel();
					if ($canEdit) SessionManager::get($event->getPlayer())->selectPos2($event->getBlock()->getPosition());
				} elseif ($item instanceof Shovel && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_BRUSH) && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_EDIT)) {
					$event->cancel();
					if ($canEdit) BrushHandler::handleBrush($item->getNamedTag(), $event->getPlayer());
				}
			} elseif ($item instanceof BlazeRod && $item->getNamedTag()->getByte("isBuilderRod", 0) === 1 && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_EDIT) && $event->getPlayer()->isCreative()) {
				$event->cancel();
				if ($canEdit) SessionManager::get($event->getPlayer())->runSettingTask(new ExtendBlockFaceTask($event->getPlayer()->getWorld()->getFolderName(), BlockVector::fromVector($event->getBlock()->getPosition()), $event->getFace()));
			}
		}
	}

	/**
	 * Interaction with air
	 * @param PlayerItemUseEvent $event
	 */
	public function onUse(PlayerItemUseEvent $event): void
	{
		try {
			$block = $event->getPlayer()->getTargetBlock(self::CREATIVE_REACH, [BlockTypeIds::WATER => true, BlockTypeIds::LAVA => true, BlockTypeIds::AIR => true]);
		} catch (Throwable) {
			//No idea why this is crashing for some users, probably caused by weird binaries / plugins
			EasyEdit::getInstance()->getLogger()->warning("Player " . $event->getPlayer()->getName() . " has thrown an exception while trying to get a target block");
			return;
		}
		$item = $event->getItem();
		if ($block === null || in_array($block->getTypeId(), [BlockTypeIds::WATER, BlockTypeIds::LAVA, BlockTypeIds::AIR], true)) {
			if ($item instanceof TieredTool && $item->getTier() === ToolTier::WOOD() && $event->getPlayer()->isCreative()) {
				if ($item instanceof Axe && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_SELECT)) {
					$event->cancel();
					try {
						$target = $event->getPlayer()->getTargetBlock(100, [BlockTypeIds::WATER => true, BlockTypeIds::LAVA => true, BlockTypeIds::AIR => true]);
					} catch (Throwable) {
						//No idea why this is crashing for some users, probably caused by weird binaries / plugins
						EasyEdit::getInstance()->getLogger()->warning("Player " . $event->getPlayer()->getName() . " has thrown an exception while trying to get a target block");
						return;
					}
					if ($target instanceof Block) {
						//HACK: Touch control sends Itemuse when starting to break a block
						//This gets triggered when breaking a block which isn't focused
						EasyEdit::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($target, $event): void {
							if ((self::$cooldown[$event->getPlayer()->getUniqueId()->getBytes()] ?? .0) < microtime(true)) {
								SessionManager::get($event->getPlayer())->selectPos2($target->getPosition());
							}
						}));
					}
				} elseif ($item instanceof Shovel && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_BRUSH) && $event->getPlayer()->hasPermission(KnownPermissions::PERMISSION_EDIT)) {
					$event->cancel();
					BrushHandler::handleBrush($item->getNamedTag(), $event->getPlayer());
				}
			}
		} elseif ($item instanceof Stick && $item->getNamedTag()->getByte("isInfoStick", 0) === 1) {
			$event->cancel();
			//We use a raytrace here as some blocks can't be interacted with
			BlockInfoTool::display(SessionManager::get($event->getPlayer()), $block);
		}
	}

	public function onTeleport(EntityTeleportEvent $event): void
	{
		$player = $event->getEntity();
		if ($player instanceof Player) {
			EasyEdit::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
				ClientSideBlockManager::updateAll($player);
			}), 2); //Network ticks after schedulers (we need updated player chunks though)
		}
	}

	public function onJoin(PlayerJoinEvent $event): void
	{
		ClientSideBlockManager::resendAll($event->getPlayer()->getName());
	}

	public function onMove(PlayerMoveEvent $event): void
	{
		ClientSideBlockManager::updateAll($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event): void
	{
		unset(self::$cooldown[$event->getPlayer()->getUniqueId()->getBytes()]);
		ChunkRefreshListener::getInstance()->clearPlayer($event->getPlayer()->getName());
	}
}