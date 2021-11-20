<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\player\Player;

class BenchmarkCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/benchmark", "Start a benchmark", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (BenchmarkManager::isRunning()) {
			Messages::send($player, "benchmark-running");
			return;
		}

		Messages::send($player, "benchmark-start");

		BenchmarkManager::start(function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results) use ($player): void {
			$i = 0;
			$resultMsg = array_map(static function (array $data) use (&$i): string {
				return Messages::replace("benchmark-result", [
					"{task}" => (string) ++$i,
					"{name}" => (string) $data[0],
					"{time}" => (string) round($data[1], 2),
					"{blocks}" => MixedUtils::humanReadable($data[2])
				]);
			}, $results);
			Messages::send($player, "benchmark-finished", [
				"{tps_avg}" => (string) round($tpsAvg, 2),
				"{tps_min}" => (string) $tpsMin,
				"{load_avg}" => (string) round($loadAvg, 2),
				"{load_max}" => (string) $loadMax,
				"{tasks}" => (string) $tasks,
				"{time}" => (string) round($time, 2),
				"{results}" => implode("\n", $resultMsg)
			]);
		});
	}
}