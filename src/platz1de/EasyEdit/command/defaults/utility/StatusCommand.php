<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\task\CollectStatsTask;
use pocketmine\utils\TextFormat;

class StatusCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/status", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		//TODO: restart, shutdown, start, kill (other command?)
		if (EditThread::getInstance()->getStatus() === EditThread::STATUS_CRASHED) {
			Messages::send($session->getPlayer(), "thread-status", [
				"{task}" => "unknown",
				"{queue}" => "unknown",
				"{status}" => TextFormat::RED . "CRASHED" . TextFormat::RESET,
				"{progress}" => "unknown"
			]);
		} else {
			$p = $session->getPlayer();
			CollectStatsTask::from(static function (string $taskName, int $taskId, string $responsiblePlayer, float $progress, int $queueLength, int $storageSize, int $currentMemory, int $realMemory) use ($p): void {
				if ($taskId !== -1) {
					$status = TextFormat::GOLD . "RUNNING" . TextFormat::RESET . ": " . self::getColoredTiming();
					$progressPart = $progress * 100 . "%";
					$task = $taskName . ":" . $taskId . " by " . $responsiblePlayer;
				} else {
					$status = TextFormat::GREEN . "OK" . TextFormat::RESET;
					$task = "none";
					$progressPart = "-";
				}

				Messages::send($p, "thread-stats", [
					"{task}" => $task,
					"{queue}" => (string) $queueLength,
					"{status}" => $status,
					"{progress}" => $progressPart,
					"{storage}" => (string) $storageSize,
					"{mem_current}" => (string) round(($currentMemory / 1024) / 1024, 2),
					"{mem_max}" => (string) round(($realMemory / 1024) / 1024, 2)
				]);
			});
		}
	}

	private static function getColoredTiming(): string
	{
		$time = microtime(true) - EditThread::getInstance()->getLastResponse();
		if ($time < 10) {
			return TextFormat::GREEN . round($time * 1000) . "ms" . TextFormat::RESET;
		}

		if ($time < 60) {
			return TextFormat::GOLD . round($time * 1000) . "ms" . TextFormat::RESET;
		}

		return TextFormat::RED . round($time * 1000) . "ms" . TextFormat::RESET;
	}
}