<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class BenchmarkTaskResult extends TaskResult
{
	/**
	 * @param string                             $world
	 * @param array{string, int, float}[] $results
	 */
	public function __construct(private string $world, private array $results) {}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$stream->putInt(count($this->results));
		foreach ($this->results as $result) {
			$stream->putString($result[0]);
			$stream->putInt($result[1]);
			$stream->putFloat($result[2]);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$count = $stream->getInt();
		$this->results = [];
		for ($i = 0; $i < $count; $i++) {
			$this->results[] = [$stream->getString(), $stream->getInt(), $stream->getFloat()];
		}
	}

	/**
	 * @return string
	 */
	public function getWorld(): string
	{
		return $this->world;
	}

	/**
	 * @return array{string, int, float}[]
	 */
	public function getResults(): array
	{
		return $this->results;
	}
}