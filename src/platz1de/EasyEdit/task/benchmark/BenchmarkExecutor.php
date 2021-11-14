<?php

namespace platz1de\EasyEdit\task\benchmark;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\logic\math\EvenPattern;
use platz1de\EasyEdit\pattern\logic\math\OddPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\task\editing\selection\CopyTask;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\BenchmarkCallbackData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;

class BenchmarkExecutor extends ExecutableTask
{
	private string $world;

	public static function from(string $world): BenchmarkExecutor
	{
		$task = new self($world);
		$task->world = $world;
		return $task;
	}

	public static function queue(): void
	{
		$name = "EasyEdit-Benchmark-" . time();
		Server::getInstance()->getWorldManager()->generateWorld($name, WorldCreationOptions::create(), false);
		TaskInputData::fromTask(self::from($name));
	}

	public function execute(): void
	{
		$results = [];

		$pos = new Vector3(0, World::Y_MIN, 0);

		//4x 3x3 Chunk cubes
		$testCube = new Cube($this->getOwner(), $this->world, new Vector3(0, World::Y_MIN, 0), new Vector3(95, World::Y_MAX - 1, 95));

		$setData = new AdditionalDataManager(true, false);
		$setData->setResultHandler(static function (EditTask $task, int $changeId) { });

		//Task #1 - set static
		SetTask::from($this->world, $this->world, $setData, $testCube, $pos, new Vector3(0, 0, 0), StaticBlock::from(VanillaBlocks::STONE()))->execute();
		$results[] = ["set static", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
		EditTaskResultCache::clear();

		$complexData = new AdditionalDataManager(true, false);
		$complexData->setResultHandler(static function (EditTask $task, int $changeId) { });

		//Task #2 - set complex
		//3D-Chess Pattern with stone and dirt
		$pattern = new Pattern([new EvenPattern([new EvenPattern([StaticBlock::from(VanillaBlocks::STONE())], PatternArgumentData::create()->useXAxis()->useZAxis()), new OddPattern([StaticBlock::from(VanillaBlocks::STONE())], PatternArgumentData::create()->useXAxis()->useZAxis()), StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useYAxis()), new EvenPattern([StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useXAxis()->useZAxis()), new OddPattern([StaticBlock::from(VanillaBlocks::DIRT())], PatternArgumentData::create()->useXAxis()->useZAxis()), StaticBlock::from(VanillaBlocks::STONE())]);
		SetTask::from($this->world, $this->world, $complexData, $testCube, $pos, new Vector3(0, 0, 0), $pattern)->execute();
		$results[] = ["set complex", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
		EditTaskResultCache::clear();

		$world = $this->world;
		$copyData = new AdditionalDataManager(false, true);
		$copyData->setResultHandler(static function (EditTask $task, int $changeId) use ($pos, &$results, $world) {
			$results[] = ["copy", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
			EditTaskResultCache::clear();

			$copied = StorageModule::getStored($changeId);
			StorageModule::cleanStored($changeId);

			$pasteData = new AdditionalDataManager(true, false);
			$pasteData->setResultHandler(static function (EditTask $task, int $changeId) use (&$results) {
				$results[] = ["paste", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
			});

			//Task #4 - paste
			DynamicPasteTask::from($world, $world, $pasteData, $copied, $pos, $pos)->execute();
		});

		//Task #3 - copy
		CopyTask::from($this->world, $this->world, $copyData, $testCube, $pos, $pos->multiply(-1))->execute();

		BenchmarkCallbackData::from($world, $results);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();
	}

	public function getTaskName(): string
	{
		return "benchmark";
	}
}