<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class SelectionContext
{
	private bool $includeCenter = false;
	private bool $includeWalls = false;
	private bool $includeVerticals = false;
	private bool $includeFilling = false;

	private float $sideThickness = 0;

	public static function empty(): SelectionContext
	{
		return new self();
	}

	public static function full(): SelectionContext
	{
		return (new self())->includeWalls()->includeVerticals()->includeFilling();
	}

	public static function hollow(): SelectionContext
	{
		return (new self())->includeWalls()->includeVerticals();
	}

	public static function walls(): SelectionContext
	{
		return (new self())->includeWalls();
	}

	public static function center(): SelectionContext
	{
		return (new self())->includeCenter();
	}

	/**
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return !$this->includeWalls && !$this->includeVerticals && !$this->includeFilling && !$this->includeCenter;
	}

	/**
	 * @return bool
	 */
	public function isFull(): bool
	{
		return $this->includeWalls && $this->includeVerticals && $this->includeFilling;
	}

	/**
	 * @return bool
	 */
	public function includesAllSides(): bool
	{
		return $this->includeWalls && $this->includeVerticals;
	}

	/**
	 * @return bool
	 */
	public function includesWalls(): bool
	{
		return $this->includeWalls;
	}

	/**
	 * @return bool
	 */
	public function includesCenter(): bool
	{
		return $this->includeCenter && !$this->includeFilling;
	}

	/**
	 * @return bool
	 */
	public function includesFilling(): bool
	{
		return $this->includeFilling;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		if ($this->isEmpty()) {
			return "none";
		}
		if ($this->isFull()) {
			return "full";
		}
		$c = [];
		if ($this->includesFilling()) {
			$c[] = "filled";
		}
		if ($this->includesAllSides()) {
			$c[] = "hollow";
		} else if ($this->includesWalls()) {
			$c[] = "walled";
		}
		if ($this->includesCenter()) {
			$c[] = "center";
		}
		return implode(" ", $c);
	}

	/**
	 * @return SelectionContext
	 */
	public function includeCenter(): SelectionContext
	{
		$this->includeCenter = true;
		return $this;
	}

	/**
	 * @param float $thickness
	 * @return SelectionContext
	 */
	public function includeWalls(float $thickness = 1): SelectionContext
	{
		$this->sideThickness = max($this->sideThickness, $thickness);
		$this->includeWalls = true;
		return $this;
	}

	/**
	 * @param float $thickness
	 * @return SelectionContext
	 */
	public function includeVerticals(float $thickness = 1): SelectionContext
	{
		$this->sideThickness = max($this->sideThickness, $thickness);
		$this->includeVerticals = true;
		return $this;
	}

	/**
	 * @return SelectionContext
	 */
	public function includeFilling(): SelectionContext
	{
		$this->includeFilling = true;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getSideThickness(): float
	{
		return $this->includesFilling() ? 1 : $this->sideThickness;
	}

	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putBool($this->includeCenter);
		$stream->putBool($this->includeWalls);
		$stream->putBool($this->includeVerticals);
		$stream->putBool($this->includeFilling);
		$stream->putFloat($this->sideThickness);
		return $stream->getBuffer();
	}

	public static function fastDeserialize(string $data): SelectionContext
	{
		$stream = new ExtendedBinaryStream($data);
		$instance = new self();
		$instance->includeCenter = $stream->getBool();
		$instance->includeWalls = $stream->getBool();
		$instance->includeVerticals = $stream->getBool();
		$instance->includeFilling = $stream->getBool();
		$instance->sideThickness = $stream->getFloat();
		return $instance;
	}
}