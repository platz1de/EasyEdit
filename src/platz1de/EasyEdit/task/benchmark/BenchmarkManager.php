<?php

namespace platz1de\EasyEdit\task\benchmark;

use Closure;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\logic\math\EvenPattern;
use platz1de\EasyEdit\pattern\logic\math\OddPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\queued\QueuedCallbackTask;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\CopyTask;
use platz1de\EasyEdit\task\selection\PasteTask;
use platz1de\EasyEdit\task\selection\SetTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Utils;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use UnexpectedValueException;

class BenchmarkManager
{
	private static bool $running = false;

	/**
	 * @param Closure $closure
	 * @param bool    $deleteLevelAfter Useful when testing core functions
	 */
	public static function start(Closure $closure, bool $deleteLevelAfter = true): void
	{
		if (self::$running) {
			throw new UnexpectedValueException("Benchmark is already running");
		}
		Utils::validateCallableSignature(static function (float $tpsAvg, float $tpsMin, float $loadAvg, float $loadMax, int $tasks, float $time, array $results): void { }, $closure);

		self::$running = true;
		$autoSave = MixedUtils::setAutoSave(PHP_INT_MAX);
		$task = EasyEdit::getInstance()->getScheduler()->scheduleRepeatingTask(new BenchmarkTask(), 1);
		$name = "EasyEdit-Benchmark-" . time();
		Server::getInstance()->getWorldManager()->generateWorld($name, WorldCreationOptions::create(), false);
		$level = Server::getInstance()->getWorldManager()->getWorldByName($name);
		if ($level === null) {
			return; //This should never happen
		}

		$results = [];

		$pos = new Position(0, World::Y_MIN, 0, $level);

		//4x 3x3 Chunk cubes
		$testCube = new Cube($name, $name, new Vector3(0, World::Y_MIN, 0), new Vector3(95, World::Y_MAX - 1, 95));

		//Task #1 - set static
		SetTask::queue($testCube, StaticBlock::from(VanillaBlocks::STONE()), $pos, function (EditTaskResult $result) use (&$results): void {
			$results[] = ["set static", $result->getTime(), $result->getChanged()];
		});

		//Task #2 - set complex
		//3D-Chess Pattern with stone and dirt
		$pattern = new Pattern([new EvenPattern([new EvenPattern([StaticBlock::from(VanillaBlocks::STONE())], PatternArgumentData::create()->useXAxis()->useZAxis()), new OddPattern([StaticBlock::from(VanillaBlocks::STONE())], PatternArgumentData::create()->useXAxis()->useZAxis()), StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useYAxis()), new EvenPattern([StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useXAxis()->useZAxis()), new OddPattern([StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useXAxis()->useZAxis()), StaticBlock::from(VanillaBlocks::STONE())]);
		SetTask::queue($testCube, $pattern, $pos, function (EditTaskResult $result) use (&$results): void {
			$results[] = ["set complex", $result->getTime(), $result->getChanged()];
		});

		//Task #3 - copy
		CopyTask::queue($testCube, $pos, function (EditTaskResult $result) use ($pos, &$results): void {
			$results[] = ["copy", $result->getTime(), $result->getChanged()];

			//Task #4 - paste
			WorkerAdapter::priority(new QueuedEditTask($result->getUndo(), new Pattern([]), $pos, PasteTask::class, new AdditionalDataManager(["edit" => true]), $pos, function (EditTaskResult $result) use (&$results): void {
				$results[] = ["paste", $result->getTime(), $result->getChanged()];
			}));
		});

		WorkerAdapter::queue(new QueuedCallbackTask(function () use ($autoSave, $level, $task, $closure, &$results, $deleteLevelAfter): void {
			$task->cancel();
			/** @var BenchmarkTask $benchmark */
			$benchmark = $task->getTask();
			$time = array_sum(array_map(static function (array $dat): float {
				return $dat[1];
			}, $results));
			$closure($benchmark->getTpsTotal(), $benchmark->getTpsMin(), $benchmark->getLoadTotal(), $benchmark->getLoadMax(), count($results), $time, $results);

			if ($deleteLevelAfter) {
				$path = $level->getProvider()->getPath();
				Server::getInstance()->getWorldManager()->unloadWorld($level);
				MixedUtils::deleteDir($path);
			}

			BenchmarkManager::$running = false;
			MixedUtils::setAutoSave($autoSave);
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