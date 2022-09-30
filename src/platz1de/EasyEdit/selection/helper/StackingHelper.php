<?php

namespace platz1de\EasyEdit\selection\helper;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;

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

	public function getNeededChunks(): array
	{
		if ($this->axis === Axis::Y) {
			return $this->parent->getNeededChunks();
		}
		$chunks = [];
		$size = (int) VectorUtils::getVectorAxis($this->parent->getSize(), $this->axis);
		for ($i = 0; $i < abs($this->amount); $i++) {
			foreach ($this->parent->offset(Vector3::zero()->getSide($this->axis << 1 | ($this->amount > 0 ? 1 : 0), $i * $size))->getNeededChunks() as $chunk) {
				$chunks[$chunk] = $chunk;
			}
		}
		return array_values($chunks);
	}

	public function shouldBeCached(int $x, int $z): bool
	{
		return false;
	}

	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
		$size = (int) VectorUtils::getVectorAxis($this->parent->getSize(), $this->axis);
		$source = (int) VectorUtils::getVectorAxis($this->parent->getPos1(), $this->axis);
		$offsetMin = (int) floor((VectorUtils::getVectorAxis($min, $this->axis) - $source) / $size);
		$offsetMax = (int) floor((VectorUtils::getVectorAxis($max, $this->axis) - $source) / $size);
		if ($this->amount < 0) {
			[$offsetMin, $offsetMax] = [-$offsetMax, -$offsetMin];
		}
		$offsetMin = max($offsetMin, 1);
		$offsetMax = min($offsetMax, abs($this->amount));
		for ($i = $offsetMin; $i <= $offsetMax; $i++) {
			$this->parent->offset(Vector3::zero()->getSide($this->axis << 1 | ($this->amount > 0 ? 1 : 0), $i * $size))->useOnBlocks($closure, $context, $min, $max);
		}
	}

	/**
	 * @param Vector3 $vector
	 * @return StackingHelper
	 */
	public function offset(Vector3 $vector): self
	{
		throw new BadMethodCallException("StackingHelper can't be offset");
	}
}