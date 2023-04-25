<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\utils\MessageComponent;
use platz1de\EasyEdit\utils\MessageCompound;
use platz1de\EasyEdit\utils\MixedUtils;

class BenchmarkCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/benchmark", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if (BenchmarkManager::isRunning()) {
			$session->sendMessage("benchmark-running");
			return;
		}

		$session->sendMessage("benchmark-start");

		$executor = $session->getPlayer();
		BenchmarkManager::start($session, static function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results) use ($executor): void {
			$resultMsg = new MessageCompound();
			foreach ($results as $i => $result) {
				$resultMsg->addComponent(new MessageComponent("benchmark-result", [
					"{task}" => (string) ($i + 1),
					"{name}" => (string) $result[0],
					"{time}" => (string) round($result[2], 2),
					"{blocks}" => MixedUtils::humanReadable($result[1])
				]));
			}
			SessionManager::get($executor)->sendMessage("benchmark-finished", [
				"{tps_avg}" => (string) round($tpsAvg, 2),
				"{tps_min}" => (string) $tpsMin,
				"{load_avg}" => (string) round($loadAvg, 2),
				"{load_max}" => (string) $loadMax,
				"{tasks}" => (string) $tasks,
				"{time}" => (string) round($time, 2),
				"{results}" => $resultMsg
			]);
		});
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from [];
	}
}