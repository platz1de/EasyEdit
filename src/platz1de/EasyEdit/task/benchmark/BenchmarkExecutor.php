<?php

namespace platz1de\EasyEdit\task\benchmark;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\task\editing\selection\CopyTask;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\output\BenchmarkCallbackData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

class BenchmarkExecutor extends ExecutableTask
{
	private SetTask $setSimpleBenchmark;
	private SetTask $setComplexBenchmark;
	private CopyTask $copyBenchmark;
	private DynamicPasteTask $pasteBenchmark;

	/**
	 * @param string $world
	 */
	public function __construct(private string $world)
	{
		parent::__construct();
	}

	public function execute(): void
	{
		$results = [];

		$pos = new OffGridBlockVector(0, World::Y_MIN, 0);

		//10x10 Chunk cube
		$testCube = new Cube($this->world, new BlockVector(0, World::Y_MIN, 0), new BlockVector(159, World::Y_MAX - 1, 159));

		//Task #1 - set static
		$start = microtime(true);
		$this->setSimpleBenchmark = new SetTask($testCube, StaticBlock::from(VanillaBlocks::STONE()));
		$this->setSimpleBenchmark->executeAssociated($this, false);
		$results[] = ["set static", $this->setSimpleBenchmark->getTotalTime(), $this->setSimpleBenchmark->getTotalBlocks(), microtime(true) - $start];
		$this->sendOutputPacket(new MessageSendData("benchmark-progress", ["{done}" => "1", "{total}" => "4"]));

		//Task #2 - set complex
		$start = microtime(true);
		//3D-Chess Pattern with stone and dirt
		$pattern = PatternParser::parseInternal("even;y(even;xz(stone).odd;xz(stone).dirt).even;xz(dirt).odd;xz(dirt).stone");
		$this->setComplexBenchmark = new SetTask($testCube, $pattern);
		$this->setComplexBenchmark->executeAssociated($this, false);
		$results[] = ["set complex", $this->setComplexBenchmark->getTotalTime(), $this->setComplexBenchmark->getTotalBlocks(), microtime(true) - $start];
		$this->sendOutputPacket(new MessageSendData("benchmark-progress", ["{done}" => "2", "{total}" => "4"]));

		//Task #3 - copy
		$start = microtime(true);
		$this->copyBenchmark = new CopyTask($testCube, $pos);
		$this->copyBenchmark->executeAssociated($this, false);
		$results[] = ["copy", $this->copyBenchmark->getTotalTime(), $this->copyBenchmark->getTotalBlocks(), microtime(true) - $start];
		$this->sendOutputPacket(new MessageSendData("benchmark-progress", ["{done}" => "3", "{total}" => "4"]));

		//Task #4 - paste
		$start = microtime(true);
		$this->pasteBenchmark = new DynamicPasteTask($this->world, $this->copyBenchmark->getResult(), $pos);
		$this->pasteBenchmark->executeAssociated($this, false);
		$results[] = ["paste", $this->pasteBenchmark->getTotalTime(), $this->pasteBenchmark->getTotalBlocks(), microtime(true) - $start];

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