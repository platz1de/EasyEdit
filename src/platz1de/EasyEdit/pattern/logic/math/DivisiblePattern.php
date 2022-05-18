<?php

namespace platz1de\EasyEdit\pattern\logic\math;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;

class DivisiblePattern extends Pattern
{
	private int $divisor;
	private bool $xAxis;
	private bool $yAxis;
	private bool $zAxis;

	/**
	 * @param int   $divisor
	 * @param bool  $xAxis
	 * @param bool  $yAxis
	 * @param bool  $zAxis
	 * @param array $pieces
	 */
	public function __construct(int $divisor, bool $xAxis, bool $yAxis, bool $zAxis, array $pieces)
	{
		parent::__construct($pieces);
		$this->divisor = $divisor;
		$this->xAxis = $xAxis;
		$this->yAxis = $yAxis;
		$this->zAxis = $zAxis;

		if ($divisor === 0) {
			throw new WrongPatternUsageException("Divisible needs a non-zero divisor");
		}
		if (!($xAxis || $yAxis || $zAxis)) {
			throw new WrongPatternUsageException("Odd needs at least one axis, zero given");
		}
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $current, Selection $total): bool
	{
		if ($this->xAxis && abs($x) % $this->divisor !== 0) {
			return false;
		}
		if ($this->yAxis && abs($y) % $this->divisor !== 0) {
			return false;
		}
		if ($this->zAxis && abs($z) % $this->divisor !== 0) {
			return false;
		}
		return true;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->divisor);
		$stream->putBool($this->xAxis);
		$stream->putBool($this->yAxis);
		$stream->putBool($this->zAxis);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->divisor = $stream->getInt();
		$this->xAxis = $stream->getBool();
		$this->yAxis = $stream->getBool();
		$this->zAxis = $stream->getBool();
	}
}