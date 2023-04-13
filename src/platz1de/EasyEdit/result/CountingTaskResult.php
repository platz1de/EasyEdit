<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CountingTaskResult extends EditTaskResult
{
	/**
	 * @param array<int, int> $blocks
	 * @param float           $time
	 */
	public function __construct(private array $blocks, float $time)
	{
		arsort($this->blocks);
		parent::__construct(array_sum($blocks), $time, StoredSelectionIdentifier::invalid());
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putInt(count($this->blocks));
		foreach ($this->blocks as $id => $count) {
			$stream->putInt($id);
			$stream->putInt($count);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
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