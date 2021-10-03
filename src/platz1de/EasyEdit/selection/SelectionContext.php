<?php

namespace platz1de\EasyEdit\selection;

class SelectionContext
{
	private bool $includeCenter = false;
	private bool $includeWalls = false;
	private bool $includeVerticals = false;
	private bool $includeFilling = false;

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
	 * @return SelectionContext
	 */
	public function includeWalls(): SelectionContext
	{
		$this->includeWalls = true;
		return $this;
	}

	/**
	 * @return SelectionContext
	 */
	public function includeVerticals(): SelectionContext
	{
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
}