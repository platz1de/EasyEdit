<?php

namespace platz1de\EasyEdit\task\benchmark;

use Closure;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\task\CallbackTask;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\selection\SetTask;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class BenchmarkManager
{
	/**
	 * @var bool
	 */
	private static $running;

	/**
	 * @param Closure $closure
	 * @param bool    $deleteLevelAfter Useful when testing core functions
	 */
	public static function start(Closure $closure, bool $deleteLevelAfter = true): void
	{
		if (self::$running) {
			throw new UnexpectedValueException("Benchmark is already running");
		}
		Utils::validateCallableSignature(function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results) { }, $closure);

		self::$running = true;
		$autoSave = MixedUtils::pauseAutoSave(); //AutoSaving produces wrong results
		$task = EasyEdit::getInstance()->getScheduler()->scheduleRepeatingTask(new BenchmarkTask(), 1);
		$name = "EasyEdit-Benchmark-" . time();
		Server::getInstance()->generateLevel($name);
		$level = Server::getInstance()->getLevelByName($name);
		if ($level === null) {
			return; //This should never happen
		}

		$results = [];

		$pos = new Position(0, 0, 0, $level);

		//4x 3x3 Chunk cubes
		$testCube = new Cube($name, $name, new Vector3(), new Vector3(95, Level::Y_MASK, 95));

		//Task #1 - set static
		SetTask::queue($testCube, new StaticBlock(BlockFactory::get(BlockIds::STONE)), $pos, function (EditTaskResult $result) use (&$results) {
			$results[] = ["set static generate", $result->getTime(), $result->getChanged()];
		});

		//Task #2 - set static
		SetTask::queue($testCube, new StaticBlock(BlockFactory::get(BlockIds::STONE)), $pos, function (EditTaskResult $result) use (&$results) {
			$results[] = ["set static", $result->getTime(), $result->getChanged()];
		});

		WorkerAdapter::queue(new CallbackTask(function () use ($autoSave, $level, $task, $closure, &$results, $deleteLevelAfter) {
			$task->cancel();
			/** @var BenchmarkTask $benchmark */
			$benchmark = $task->getTask();
			$time = array_sum(array_map(static function (array $dat) {
				return $dat[1];
			}, $results));
			$closure($benchmark->getTpsTotal(), $benchmark->getTpsMin(), $benchmark->getLoadTotal(), $benchmark->getLoadMax(), count($results), $time, $results);

			if ($deleteLevelAfter) {
				$path = $level->getProvider()->getPath();
				Server::getInstance()->unloadLevel($level);
				MixedUtils::deleteDir($path);
			}

			BenchmarkManager::$running = false;

			if($autoSave) {
				MixedUtils::continueAutoSave();
			}
		}));
	}

	/**
	 * @return bool
	 */
	public static function isRunning(): bool
	{
		return self::$running;
	}
}