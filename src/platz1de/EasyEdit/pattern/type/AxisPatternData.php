<?php

namespace platz1de\EasyEdit\pattern\type;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

trait AxisPatternData
{
	private bool $xAxis;
	private bool $yAxis;
	private bool $zAxis;

	/**
	 * @param AxisArgumentWrapper $axi
	 * @param Pattern[]           $pieces
	 */
	public function __construct(AxisArgumentWrapper $axi, array $pieces)
	{
		parent::__construct($pieces);
		$this->xAxis = $axi->getXAxis();
		$this->yAxis = $axi->getYAxis();
		$this->zAxis = $axi->getZAxis();

		if (!($this->xAxis || $this->yAxis || $this->zAxis)) {
			throw new WrongPatternUsageException("Odd needs at least one axis, zero given");
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBool($this->xAxis);
		$stream->putBool($this->yAxis);
		$stream->putBool($this->zAxis);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->xAxis = $stream->getBool();
		$this->yAxis = $stream->getBool();
		$this->zAxis = $stream->getBool();
	}
}