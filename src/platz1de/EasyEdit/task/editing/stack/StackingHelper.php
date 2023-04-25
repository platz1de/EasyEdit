<?php

namespace platz1de\EasyEdit\task\editing\stack;

use BadMethodCallException;
use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\StackedConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use pocketmine\math\Axis;
use pocketmine\world\World;

class StackingHelper extends Selection
{
	private Selection $parent;
	private int $axis;
	private int $amount;

	/**
	 * @param Selection         $selection
	 * @param BlockOffsetVector $direction
	 */
	public function __construct(Selection $selection, BlockOffsetVector $direction)
	{
		if ($selection instanceof self) {
			throw new BadMethodCallException("Cannot stack a stacked selection");
		}
		$this->parent = $selection;
		if ($direction->y !== 0) {
			$this->axis = Axis::Y;
		} elseif ($direction->x !== 0) {
			$this->axis = Axis::X;
		} else {
			$this->axis = Axis::Z;
		}
		$this->amount = $direction->getComponent($this->axis);
		$offset = $selection->getSize()->getComponent($this->axis);
		if ($this->amount > 0) {
			parent::__construct($selection->getWorldName(), $selection->getPos1()->addComponent($this->axis, $offset), $selection->getPos2()->addComponent($this->axis, $offset * $this->amount));
		} else {
			parent::__construct($selection->getWorldName(), $selection->getPos1()->addComponent($this->axis, $offset * $this->amount), $selection->getPos2()->addComponent($this->axis, -$offset));
		}
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		if ($this->axis === Axis::Y) {
			return $this->parent->getNeededChunks();
		}
		//TODO: offset parent to not load chunks that are not needed
		$size = $this->parent->getSize();
		$min = $this->parent->getPos1();
		$max = $this->parent->getPos2();
		if ($this->amount > 0) {
			$min = $min->addComponent($this->axis, $size->getComponent($this->axis));
			$max = $max->addComponent($this->axis, $size->getComponent($this->axis) * $this->amount);
		} else {
			$min = $min->addComponent($this->axis, $size->getComponent($this->axis) * $this->amount);
			$max = $max->addComponent($this->axis, -$size->getComponent($this->axis));
		}
		$chunks = [];
		for ($x = $min->x >> 4; $x <= $max->x >> 4; $x++) {
			for ($z = $min->z >> 4; $z <= $max->z >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		yield new StackedConstructor($closure, $this->parent, $context, $this->axis, $this->amount);
	}

	/**
	 * @return bool
	 */
	public function isCopying(): bool
	{
		return $this->axis !== Axis::Y && $this->parent->getSize()->getComponent($this->axis) > ChunkRequestManager::MAX_REQUEST * 8;
	}

	/**
	 * @return int
	 */
	public function getAxis(): int
	{
		return $this->axis;
	}

	/**
	 * @return int
	 */
	public function getAmount(): int
	{
		return $this->amount;
	}
}