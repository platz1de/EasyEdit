<?php

namespace platz1de\EasyEdit\pattern\logic\math;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\type\AxisArgumentWrapper;
use platz1de\EasyEdit\pattern\type\AxisPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;

class DivisiblePattern extends Pattern
{
	use AxisPatternData {
		AxisPatternData::__construct as private __constructAxisPatternData;
		AxisPatternData::putData as private putAxisPatternData;
		AxisPatternData::parseData as private parseAxisPatternData;
	}

	private int $divisor;

	/**
	 * @param AxisArgumentWrapper $axi
	 * @param int                 $divisor
	 * @param Pattern[]           $pieces
	 */
	public function __construct(AxisArgumentWrapper $axi, int $divisor, array $pieces)
	{
		parent::__construct($pieces);
		$this->divisor = $divisor;
		$this->__constructAxisPatternData($axi, $pieces);

		if ($divisor <= 0) {
			throw new WrongPatternUsageException("Divisible needs a non-zero divisor");
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
		$this->putAxisPatternData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->divisor = $stream->getInt();
		$this->parseAxisPatternData($stream);
	}
}