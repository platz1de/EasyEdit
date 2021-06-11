<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use pocketmine\Player;

class BenchmarkCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/benchmark", "Start a benchmark", "easyedit.command.thread");
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		Messages::send($player, "benchmark-start");

		BenchmarkManager::start(function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results) use ($player) {
			$i = 0;
			$resultMsgs = array_map(static function (array $data) use (&$i) {
				return Messages::replace("benchmark-result", [
					"{task}" => ++$i,
					"{name}" => $data[0],
					"{time}" => round($data[1], 2),
					"{blocks}" => $data[2]
				]);
			}, $results);
			Messages::send($player, "benchmark-finished", [
				"{tps_avg}" => round($tpsAvg, 2),
				"{tps_min}" => $tpsMin,
				"{load_avg}" => round($loadAvg, 2),
				"{load_max}" => $loadMax,
				"{tasks}" => $tasks,
				"{time}" => round($time, 2),
				"{results}" => implode("\n", $resultMsgs)
			]);
		});
	}
}