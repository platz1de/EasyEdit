<?php

namespace platz1de\EasyEdit\math;

use InvalidArgumentException;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;

abstract class BaseVector
{
	//TODO: make these read-only
	public int $x;
	public int $y;
	public int $z;

	final public function __construct(int $x, int $y, int $z)
	{
		$this->validate($x, $y, $z);
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	abstract protected function validate(&$x, &$y, &$z): void;

	public static function fromVector(Vector3 $vector): self
	{
		return new static($vector->x, $vector->y, $vector->z);
	}

	public static function zero(): self
	{
		//TODO: Reuse this like pmmp, when php 8.1 is mandatory
		return new static(0, 0, 0);
	}

	/**
	 * @param int $axis
	 * @param int $amount
	 * @return static
	 */
	public function addComponent(int $axis, int $amount): self
	{
		return match ($axis) {
			Axis::X => new static($this->x + $amount, $this->y, $this->z),
			Axis::Y => new static($this->x, $this->y + $amount, $this->z),
			Axis::Z => new static($this->x, $this->y, $this->z + $amount),
			default => throw new InvalidArgumentException("Invalid axis $axis"),
		};
	}

	public function getComponent(int $axis): int
	{
		return match ($axis) {
			Axis::X => $this->x,
			Axis::Y => $this->y,
			Axis::Z => $this->z,
			default => throw new InvalidArgumentException("Invalid axis $axis"),
		};
	}

	public function add(int $x, int $y, int $z): self
	{
		return new static($this->x + $x, $this->y + $y, $this->z + $z);
	}

	public function up(int $amount = 1): self
	{
		return $this->addComponent(Axis::Y, $amount);
	}

	public function down(int $amount = 1): self
	{
		return $this->addComponent(Axis::Y, -$amount);
	}
}