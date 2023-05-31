<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class BenchmarkTaskResult extends TaskResult
{
	/**
	 * @param array{string, int, float}[] $results
	 */
	public function __construct(private array $results) {}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->results));
		foreach ($this->results as $result) {
			$stream->putString($result[0]);
			$stream->putInt($result[1]);
			$stream->putFloat($result[2]);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		$this->results = [];
		for ($i = 0; $i < $count; $i++) {
			$this->results[] = [$stream->getString(), $stream->getInt(), $stream->getFloat()];
		}
	}

	/**
	 * @return array{string, int, float}[]
	 */
	public function getResults(): array
	{
		return $this->results;
	}
}