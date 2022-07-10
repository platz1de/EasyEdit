<?php

namespace platz1de\EasyEdit\task\benchmark;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\task\editing\selection\CopyTask;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\BenchmarkCallbackData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use UnexpectedValueException;

class BenchmarkExecutor extends ExecutableTask
{
	private string $world;
	private SetTask $setSimpleBenchmark;
	private SetTask $setComplexBenchmark;
	private CopyTask $copyBenchmark;
	private DynamicPasteTask $pasteBenchmark;

	public static function from(string $world): BenchmarkExecutor
	{
		$task = new self();
		$task->world = $world;
		return $task;
	}

	public function execute(): void
	{
		$results = [];

		$pos = new Vector3(0, World::Y_MIN, 0);

		//4x 3x3 Chunk cubes
		$testCube = new Cube($this->world, new Vector3(0, World::Y_MIN, 0), new Vector3(95, World::Y_MAX - 1, 95));

		$setData = new AdditionalDataManager(true, false);
		$setData->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId) { });

		//Task #1 - set static
		$this->setSimpleBenchmark = SetTask::from($this->world, $setData, $testCube, $pos, StaticBlock::from(VanillaBlocks::STONE()));
		$this->setSimpleBenchmark->executeAssociated($this);
		$results[] = ["set static", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
		EditTaskResultCache::clear();

		$complexData = new AdditionalDataManager(true, false);
		$complexData->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId) { });

		//Task #2 - set complex
		//3D-Chess Pattern with stone and dirt
		$pattern = PatternParser::parseInternal("even;y(even;xz(stone).odd;xz(stone).dirt).even;xz(dirt).odd;xz(dirt).stone");
		$this->setComplexBenchmark = SetTask::from($this->world, $complexData, $testCube, $pos, $pattern);
		$this->setComplexBenchmark->executeAssociated($this);
		$results[] = ["set complex", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
		EditTaskResultCache::clear();

		$world = $this->world;
		$copyData = new AdditionalDataManager(false, true);
		$copyData->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId) use ($pos, &$results, $world, &$pasteBenchmark) {
			$results[] = ["copy", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
			EditTaskResultCache::clear();

			if ($changeId === null) {
				throw new UnexpectedValueException("ChangeId is null");
			}

			$copied = StorageModule::mustGetDynamic($changeId);
			StorageModule::cleanStored($changeId);

			$pasteData = new AdditionalDataManager(true, false);
			$pasteData->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId) use (&$results) {
				$results[] = ["paste", EditTaskResultCache::getTime(), EditTaskResultCache::getChanged()];
			});

			//Task #4 - paste
			$pasteBenchmark = DynamicPasteTask::from($world, $pasteData, $copied, $pos);
		});

		//Task #3 - copy
		$this->copyBenchmark = CopyTask::from($this->world, $copyData, $testCube, $pos);
		$this->copyBenchmark->executeAssociated($this);

		if ($pasteBenchmark === null) {
			throw new UnexpectedValueException("Failed to handle result of copy benchmark");
		}
		$this->pasteBenchmark = $pasteBenchmark;
		/** @var DynamicPasteTask $pasteBenchmark */
		$pasteBenchmark->executeAssociated($this);

		$this->sendOutputPacket(new BenchmarkCallbackData($world, $results));
	}

	public function getProgress(): float
	{
		return ($this->setSimpleBenchmark->getProgress() + (isset($this->setComplexBenchmark) ? $this->setComplexBenchmark->getProgress() : 0) + (isset($this->copyBenchmark) ? $this->copyBenchmark->getProgress() : 0) + (isset($this->pasteBenchmark) ? $this->pasteBenchmark->getProgress() : 0)) / 4;
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