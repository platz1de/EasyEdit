<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\task\benchmark\BenchmarkManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class BenchmarkCallbackData extends OutputData
{
	/**
	 * @param string                                  $world
	 * @param array<array{string, float, int, float}> $result
	 */
	public function __construct(private string $world, private array $result) {}

	public function handle(): void
	{
		BenchmarkManager::benchmarkCallback($this->world, $this->result);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$stream->putInt(count($this->result));
		foreach ($this->result as $result) {
			$stream->putString($result[0]);
			$stream->putFloat($result[1]);
			$stream->putInt($result[2]);
			$stream->putFloat($result[3]);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$count = $stream->getInt();
		$this->result = [];
		for ($i = 0; $i < $count; $i++) {
			$this->result[] = [$stream->getString(), $stream->getFloat(), $stream->getInt(), $stream->getFloat()];
		}
	}
}