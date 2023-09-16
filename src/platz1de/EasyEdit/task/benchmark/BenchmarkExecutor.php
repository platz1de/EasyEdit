<?php

namespace platz1de\EasyEdit\task\benchmark;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\result\BenchmarkTaskResult;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\task\editing\CopyTask;
use platz1de\EasyEdit\task\editing\DynamicPasteTask;
use platz1de\EasyEdit\task\editing\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\output\TaskNotifyData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

/**
 * @extends ExecutableTask<BenchmarkTaskResult>
 */
class BenchmarkExecutor extends ExecutableTask
{
	/**
	 * @var array{string, int, float}[]
	 */
	private array $results = [];

	/**
	 * @param string $world
	 */
	public function __construct(private string $world)
	{
		parent::__construct();
	}

	public function calculateEffectiveComplexity(): int
	{
		return -1;
	}

	protected function executeInternal(): BenchmarkTaskResult
	{
		$this->results = [];

		$pos = new OffGridBlockVector(0, World::Y_MIN, 0);

		//10x10 Chunk cube
		$testCube = new Cube($this->world, new BlockVector(0, World::Y_MIN, 0), new BlockVector(159, World::Y_MAX - 1, 159));

		//Task #1 - set static
		$start = microtime(true);
		$res = (new SetTask($testCube, StaticBlock::from(VanillaBlocks::STONE())))->executeInternal();
		$this->results[] = ["set static", $res->getAffected(), microtime(true) - $start];
		$this->sendOutputPacket(new TaskNotifyData(1));

		//Task #2 - set complex
		$start = microtime(true);
		//3D-Chess Pattern with stone and dirt
		$pattern = PatternParser::parseInternal("even;y(even;xz(stone).odd;xz(stone).dirt).even;xz(dirt).odd;xz(dirt).stone");
		$res = (new SetTask($testCube, $pattern))->executeInternal();
		$this->results[] = ["set complex", $res->getAffected(), microtime(true) - $start];
		$this->sendOutputPacket(new TaskNotifyData(2));

		//Task #3 - copy
		$start = microtime(true);
		$res = (new CopyTask($testCube, $pos))->executeInternal(); //TODO: Don't save the selection (literal memory leak)
		$this->results[] = ["copy", $res->getAffected(), microtime(true) - $start];
		$this->sendOutputPacket(new TaskNotifyData(3));

		//Task #4 - paste
		$start = microtime(true);
		$res = (new DynamicPasteTask($this->world, $res->getSelection(), $pos))->executeInternal();
		$this->results[] = ["paste", $res->getAffected(), microtime(true) - $start];

		return new BenchmarkTaskResult($this->results);
	}

	public function attemptRecovery(): BenchmarkTaskResult
	{
		return new BenchmarkTaskResult($this->results);
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