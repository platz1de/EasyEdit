<?php

namespace platz1de\EasyEdit\selection\constructor;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class StackedConstructor extends ShapeConstructor
{
	/**
	 * @var ShapeConstructor[]
	 */
	private array $parents;

	public function __construct(Closure $closure, private Selection $selection, SelectionContext $context, private int $axis, private int $amount)
	{
		parent::__construct($closure);
		$this->parents = iterator_to_array($selection->asShapeConstructors($closure, $context));
	}

	public function getBlockCount(): int
	{
		return array_sum(array_map(static fn(ShapeConstructor $constructor): int => $constructor->getBlockCount(), $this->parents)) * $this->amount;
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$size = $this->selection->getSize()->getComponent($this->axis);
		$source = $this->selection->getPos1()->getComponent($this->axis);
		$offsetMin = (int) floor(($min->getComponent($this->axis) - $source) / $size);
		$offsetMax = (int) floor(($max->getComponent($this->axis) - $source) / $size);
		if ($this->amount < 0) {
			[$offsetMin, $offsetMax] = [$offsetMax, $offsetMin];
			$offsetMin = max($offsetMin, $this->amount);
			$offsetMax = min($offsetMax, -1);
		} else {
			$offsetMin = max($offsetMin, 1);
			$offsetMax = min($offsetMax, $this->amount);
		}
		for ($i = $offsetMin; $i <= $offsetMax; $i++) {
			foreach ($this->parents as $parent) {
				$parent->offset(BlockOffsetVector::zero()->addComponent($this->axis, $i * $size))->moveTo($chunk);
			}
		}
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		throw new BadMethodCallException("Stacked cubes can't be offset");
	}
}