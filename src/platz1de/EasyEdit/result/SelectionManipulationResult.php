<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class SelectionManipulationResult extends TaskResult
{
	/**
	 * @param int $changed
	 */
	public function __construct(private int $changed) {}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->changed);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->changed = $stream->getInt();
	}

	/**
	 * @return int
	 */
	public function getChanged(): int
	{
		return $this->changed;
	}
}