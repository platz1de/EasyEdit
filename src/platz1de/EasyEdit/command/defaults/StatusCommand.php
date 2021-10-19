<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StatusCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/status", "Check on the EditThread", "easyedit.command.thread");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		//TODO: restart, shutdown, start, kill (other command?)
		$manager = EditAdapter::getCurrentTask();
		if ($manager instanceof QueuedEditTask) {
			$manager = $manager->getExecutor();
			$task = $manager->getCurrent()->getTaskName() . ":" . $manager->getCurrent()->getId() . " by " . $manager->getQueued()->getSelection()->getPlayer();
			$progress = ($manager->getTotalLength() - $manager->getLength()) . "/" . $manager->getTotalLength() . " (" . round(($manager->getTotalLength() - $manager->getLength()) / $manager->getTotalLength() * 100, 1) . "%)";
		} else {
			$task = "none";
			$progress = "-";
		}

		switch (EditThread::getInstance()->getStatus()) {
			case EditThread::STATUS_IDLE:
				$status = TextFormat::GREEN . "OK" . TextFormat::RESET;
				break;
			case EditThread::STATUS_PREPARING:
				$status = TextFormat::AQUA . "PREPARING" . TextFormat::RESET . ": " . self::getColoredTiming();
				break;
			case EditThread::STATUS_RUNNING:
				$status = TextFormat::GOLD . "RUNNING" . TextFormat::RESET . ": " . self::getColoredTiming();
				break;
			default:
				return;
		}

		Messages::send($player, "thread-status", [
			"{task}" => $task,
			"{queue}" => (string) EditAdapter::getQueueLength(),
			"{status}" => $status,
			"{progress}" => $progress
		]);
	}

	private static function getColoredTiming(): string
	{
		$time = microtime(true) - EditThread::getInstance()->getLastResponse();
		if ($time < 1) {
			return TextFormat::GREEN . round($time * 1000) . "ms" . TextFormat::RESET;
		}

		if ($time < 10) {
			return TextFormat::GOLD . round($time * 1000) . "ms" . TextFormat::RESET;
		}

		return TextFormat::RED . round($time * 1000) . "ms" . TextFormat::RESET;
	}
}