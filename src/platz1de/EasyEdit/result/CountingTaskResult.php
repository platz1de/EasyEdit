<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CountingTaskResult extends TaskResult
{
	/**
	 * @param array<int, int> $blocks
	 */
	public function __construct(private array $blocks)
	{
		arsort($this->blocks);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->blocks));
		foreach ($this->blocks as $id => $count) {
			$stream->putInt($id);
			$stream->putInt($count);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$blocks = [];
		for ($i = $stream->getInt(); $i > 0; $i--) {
			$blocks[$stream->getInt()] = $stream->getInt();
		}
		$this->blocks = $blocks;
	}

	/**
	 * @return array<int, int>
	 */
	public function getBlocks(): array
	{
		return $this->blocks;
	}
}