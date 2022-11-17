<?php

namespace platz1de\EasyEdit\task\editing\selection\stack;

use BadMethodCallException;
use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\constructor\StackedConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class StackingHelper extends Selection
{
	private Selection $parent;
	private int $axis;
	private int $amount;

	/**
	 * @param Selection $selection
	 * @param Vector3   $direction
	 */
	public function __construct(Selection $selection, Vector3 $direction)
	{
		if ($selection instanceof self) {
			throw new BadMethodCallException("Cannot stack a stacked selection");
		}
		$this->parent = $selection;
		if ($direction->getFloorY() !== 0) {
			$this->axis = Axis::Y;
		} elseif ($direction->getFloorX() !== 0) {
			$this->axis = Axis::X;
		} else {
			$this->axis = Axis::Z;
		}
		$this->amount = (int) floor(VectorUtils::getVectorAxis($direction, $this->axis));
		$offset = (int) floor(VectorUtils::getVectorAxis($selection->getSize(), $this->axis));
		if ($this->amount > 0) {
			parent::__construct($selection->getWorldName(), $selection->getPos1()->getSide($this->axis << 1 | 1, $offset), $selection->getPos2()->getSide($this->axis << 1 | 1, $offset * $this->amount));
		} else {
			parent::__construct($selection->getWorldName(), $selection->getPos1()->getSide($this->axis << 1, $offset * -$this->amount), $selection->getPos2()->getSide($this->axis << 1, $offset));
		}
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		if ($this->axis === Axis::Y || $this->isCopying()) {
			return $this->parent->getNeededChunks();
		}
		//TODO: offset parent to not load chunks that are not needed
		$size = $this->parent->getSize();
		$min = $this->parent->getCubicStart();
		$max = $this->parent->getCubicEnd();
		$offset = VectorUtils::getVectorAxis($size, $this->axis);
		$offsetVector = $this->axis === Axis::X ? new Vector3($offset, 0, 0) : new Vector3(0, 0, $offset);
		if ($this->amount > 0) {
			$min = $min->addVector($offsetVector);
			$max = $max->addVector($offsetVector->multiply($this->amount));
		} else {
			$min = $min->subtractVector($offsetVector->multiply(-$this->amount));
			$max = $max->subtractVector($offsetVector);
		}
		$chunks = [];
		for ($x = $min->getFloorX() >> 4; $x <= $max->getFloorX() >> 4; $x++) {
			for ($z = $min->getFloorZ() >> 4; $z <= $max->getFloorZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	public function shouldBeCached(int $x, int $z): bool
	{
		return false;
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
		return $this->axis !== Axis::Y && VectorUtils::getVectorAxis($this->parent->getSize(), $this->axis) > ChunkRequestManager::MAX_REQUEST * 8;
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