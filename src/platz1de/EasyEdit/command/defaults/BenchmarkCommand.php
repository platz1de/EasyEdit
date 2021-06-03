<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\task\PieceManager;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

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
			$resultMsgs = array_map(function (array $data) use (&$i) {
				return Messages::replace("benchmark-result", [
					"{task}" => ++$i,
					"{name}" => $data[0],
					"{time}" => $data[1],
					"{blocks}" => $data[2]
				]);
			}, $results);
			Messages::send($player, "benchmark-finished", [
				"{tps_avg}" => $tpsAvg,
				"{tps_min}" => $tpsMin,
				"{load_avg}" => $loadAvg,
				"{load_max}" => $loadMax,
				"{tasks}" => $tasks,
				"{time}" => $time,
				"{results}" => implode("\n", $resultMsgs)
			]);
		});
	}
}