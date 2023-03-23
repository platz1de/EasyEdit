<?php

namespace platz1de\EasyEdit\math;

use InvalidArgumentException;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;

abstract class BaseVector
{
	//TODO: make these read-only
	//TODO: there are still a ton of places where we modify vectors in place, which is a bad idea
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

	/**
	 * @param Vector3 $vector
	 * @return static
	 */
	public static function fromVector(Vector3 $vector): self
	{
		return new static($vector->x, $vector->y, $vector->z);
	}

	public function toVector(): Vector3
	{
		return new Vector3($this->x, $this->y, $this->z);
	}

	/**
	 * @return static
	 */
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

	/**
	 * @param int $axis
	 * @param int $amount
	 * @return static
	 */
	public function setComponent(int $axis, int $amount): self
	{
		return match ($axis) {
			Axis::X => new static($amount, $this->y, $this->z),
			Axis::Y => new static($this->x, $amount, $this->z),
			Axis::Z => new static($this->x, $this->y, $amount),
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

	/**
	 * @param static $a
	 * @param static $b
	 * @return static
	 */
	public static function minComponents(self $a, self $b): self
	{
		return new static(min($a->x, $b->x), min($a->y, $b->y), min($a->z, $b->z));
	}

	/**
	 * @param static $a
	 * @param static $b
	 * @return static
	 */
	public static function maxComponents(self $a, self $b): self
	{
		return new static(max($a->x, $b->x), max($a->y, $b->y), max($a->z, $b->z));
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