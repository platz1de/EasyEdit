<?php

namespace platz1de\EasyEdit\task\benchmark;

use Closure;

class BenchmarkManager
{
	private static bool $running = false;

	/**
	 * @param Closure $closure
	 * @param bool    $deleteWorldAfter Useful when testing core functions
	 */
	public static function start(Closure $closure, bool $deleteWorldAfter = true): void
	{
		//TODO: I broke this again...
		/*if (self::$running) {
			throw new UnexpectedValueException("Benchmark is already running");
		}
		Utils::validateCallableSignature(static function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results): void { }, $closure);

		self::$running = true;
		$autoSave = MixedUtils::setAutoSave(PHP_INT_MAX);
		$task = EasyEdit::getInstance()->getScheduler()->scheduleRepeatingTask(new BenchmarkTask(), 1);
		$name = "EasyEdit-Benchmark-" . time();
		Server::getInstance()->getWorldManager()->generateWorld($name, WorldCreationOptions::create(), false);
		$world = Server::getInstance()->getWorldManager()->getWorldByName($name);
		if ($world === null) {
			return; //This should never happen
		}

		$results = [];

		$pos = new Vector3(0, World::Y_MIN, 0);

		//4x 3x3 Chunk cubes
		$testCube = new Cube($name, $name, new Vector3(0, World::Y_MIN, 0), new Vector3(95, World::Y_MAX - 1, 95));

		//Task #1 - set static
		EditAdapter::queue(new QueuedEditTask($testCube, StaticBlock::from(VanillaBlocks::STONE()), $world->getFolderName(), $pos, SetTask::class, new AdditionalDataManager(true, false), new Vector3(0, 0, 0)), function (HistoryCacheData $result) use (&$results): void {
			$results[] = ["set static", $result->getTime(), $result->getChanges()];
		});

		//Task #2 - set complex
		//3D-Chess Pattern with stone and dirt
		$pattern = new Pattern([new EvenPattern([new EvenPattern([StaticBlock::from(VanillaBlocks::STONE())], PatternArgumentData::create()->useXAxis()->useZAxis()), new OddPattern([StaticBlock::from(VanillaBlocks::STONE())], PatternArgumentData::create()->useXAxis()->useZAxis()), StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useYAxis()), new EvenPattern([StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useXAxis()->useZAxis()), new OddPattern([StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useXAxis()->useZAxis()), StaticBlock::from(VanillaBlocks::STONE())]);
		EditAdapter::queue(new QueuedEditTask($testCube, $pattern, $world->getFolderName(), $pos, SetTask::class, new AdditionalDataManager(true, false), new Vector3(0, 0, 0)), function (HistoryCacheData $result) use (&$results): void {
			$results[] = ["set complex", $result->getTime(), $result->getChanges()];
		});

		//Task #3 - copy
		EditAdapter::queue(new QueuedEditTask($testCube, new Pattern([]), $world->getFolderName(), $pos, CopyTask::class, new AdditionalDataManager(false, true), new Vector3(0, 0, 0)), function (HistoryCacheData $result) use ($name, $task, $closure, $deleteWorldAfter, $autoSave, $world, $pos, &$results): void {
			$results[] = ["copy", $result->getTime(), $result->getChanges()];

			//TODO: prioritize
			//Task #4 - paste
			EditAdapter::queue(new QueuedEditTask(new LinkedBlockListSelection($name, $world->getFolderName(), $result->getChangeId()), new Pattern([]), $world->getFolderName(), $pos, PasteTask::class, new AdditionalDataManager(true, false), $pos), function (HistoryCacheData $result) use ($autoSave, $world, $deleteWorldAfter, $closure, $task, &$results): void {
				$results[] = ["paste", $result->getTime(), $result->getChanges()];
				$task->cancel();
				$benchmark = $task->getTask();
				$time = array_sum(array_map(static function (array $dat): float {
					return $dat[1];
				}, $results));
				$closure($benchmark->getTpsTotal(), $benchmark->getTpsMin(), $benchmark->getLoadTotal(), $benchmark->getLoadMax(), count($results), $time, $results);

				if ($deleteWorldAfter) {
					$path = $world->getProvider()->getPath();
					Server::getInstance()->getWorldManager()->unloadWorld($world);
					MixedUtils::deleteDir($path);
				}

				BenchmarkManager::$running = false;
				MixedUtils::setAutoSave($autoSave);
			});
		});*/
	}

	/**
	 * @return bool
	 */
	public static function isRunning(): bool
	{
		return self::$running;
	}
}