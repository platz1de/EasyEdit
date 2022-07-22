<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\utils\Messages;
use platz1de\EasyEdit\utils\MixedUtils;

class BenchmarkCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/benchmark", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		if (BenchmarkManager::isRunning()) {
			$session->sendMessage("benchmark-running");
			return;
		}

		$session->sendMessage("benchmark-start");

		$executor = $session->getPlayer();
		BenchmarkManager::start(static function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results) use ($executor): void {
			$i = 0;
			$resultMsg = array_map(static function (array $data) use (&$i): string {
				return Messages::replace("benchmark-result", [
					"{task}" => (string) ++$i,
					"{name}" => (string) $data[0],
					"{time}" => (string) round($data[1], 2),
					"{blocks}" => MixedUtils::humanReadable($data[2])
				]);
			}, $results);
			Messages::send($executor, "benchmark-finished", [
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