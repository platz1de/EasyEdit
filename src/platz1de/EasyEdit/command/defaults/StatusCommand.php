<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\task\CollectStatsTask;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StatusCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/status", "Check on the EditThread", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		//TODO: restart, shutdown, start, kill (other command?)
		if (EditThread::getInstance()->getStatus() === EditThread::STATUS_CRASHED) {
			Messages::send($player, "thread-status", [
				"{task}" => "unknown",
				"{queue}" => "unknown",
				"{status}" => TextFormat::RED . "CRASHED" . TextFormat::RESET,
				"{progress}" => "unknown"
			]);
		} else {
			$p = $player->getName();
			CollectStatsTask::from(function (string $taskName, int $taskId, string $responsiblePlayer, int $totalPieces, int $piecesLeft, int $queueLength) use ($p): void {
				if ($totalPieces !== -1) {
					$status = TextFormat::GOLD . "RUNNING" . TextFormat::RESET . ": " . self::getColoredTiming();
					$progress = ($totalPieces - $piecesLeft) . "/" . $totalPieces . " (" . round(($totalPieces - $piecesLeft) / $totalPieces * 100, 1) . "%)";
					$task = $taskName . ":" . $taskId . " by " . $responsiblePlayer;
				} else {
					$status = TextFormat::GREEN . "OK" . TextFormat::RESET;
					$task = "none";
					$progress = "-";
				}

				Messages::send($p, "thread-status", [
					"{task}" => $task,
					"{queue}" => (string) $queueLength,
					"{status}" => $status,
					"{progress}" => $progress
				]);
			});
		}
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