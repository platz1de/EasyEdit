<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class SelectionManipulationResult extends TaskResult
{
	/**
	 * @param int   $changed
	 * @param float $time TODO: Remove this
	 */
	public function __construct(private int $changed, private float $time) {}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->changed);
		$stream->putFloat($this->time);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->changed = $stream->getInt();
		$this->time = $stream->getFloat();
	}

	/**
	 * @return int
	 */
	public function getChanged(): int
	{
		return $this->changed;
	}

	/**
	 * @return float
	 */
	public function getTime(): float
	{
		return $this->time;
	}
}