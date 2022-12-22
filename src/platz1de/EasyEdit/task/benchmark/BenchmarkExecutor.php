<?php

namespace platz1de\EasyEdit\task\benchmark;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\task\editing\selection\CopyTask;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\BenchmarkCallbackData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class BenchmarkExecutor extends ExecutableTask
{
	private string $world;
	private SetTask $setSimpleBenchmark;
	private SetTask $setComplexBenchmark;
	private CopyTask $copyBenchmark;
	private DynamicPasteTask $pasteBenchmark;

	/**
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		$this->world = $world;
		parent::__construct();
	}

	public function execute(): void
	{
		$results = [];

		$pos = new Vector3(0, World::Y_MIN, 0);

		//4x 3x3 Chunk cubes
		$testCube = new Cube($this->world, new Vector3(0, World::Y_MIN, 0), new Vector3(95, World::Y_MAX - 1, 95));

		//Task #1 - set static
		$this->setSimpleBenchmark = new SetTask($testCube, StaticBlock::from(VanillaBlocks::STONE()));
		$this->setSimpleBenchmark->executeAssociated($this, false);
		$results[] = ["set static", $this->setSimpleBenchmark->getTotalTime(), $this->setSimpleBenchmark->getTotalBlocks()];
		StorageModule::clear();

		//Task #2 - set complex
		//3D-Chess Pattern with stone and dirt
		$pattern = PatternParser::parseInternal("even;y(even;xz(stone).odd;xz(stone).dirt).even;xz(dirt).odd;xz(dirt).stone");
		$this->setComplexBenchmark = new SetTask($testCube, $pattern);
		$this->setComplexBenchmark->executeAssociated($this, false);
		$results[] = ["set complex", $this->setComplexBenchmark->getTotalTime(), $this->setComplexBenchmark->getTotalBlocks()];
		StorageModule::clear();

		//Task #3 - copy
		$this->copyBenchmark = new CopyTask($testCube, $pos);
		$this->copyBenchmark->executeAssociated($this, false);
		$results[] = ["copy", $this->copyBenchmark->getTotalTime(), $this->copyBenchmark->getTotalBlocks()];

		$copied = StorageModule::mustGetDynamic($id = StorageModule::finishCollecting());
		StorageModule::cleanStored($id);

		//Task #4 - paste
		$this->pasteBenchmark = new DynamicPasteTask($this->world, $copied, $pos);
		$this->pasteBenchmark->executeAssociated($this, false);
		$results[] = ["paste", $this->pasteBenchmark->getTotalTime(), $this->pasteBenchmark->getTotalBlocks()];
		StorageModule::clear();

		$this->sendOutputPacket(new BenchmarkCallbackData($this->world, $results));
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