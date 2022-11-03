<?php

namespace platz1de\EasyEdit\selection\constructor;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class StackedConstructor extends ShapeConstructor
{
	/**
	 * @var ShapeConstructor[]
	 */
	private array $parents;
	private Selection $selection;
	private int $axis;
	private int $amount;

	public function __construct(Closure $closure, Selection $selection, SelectionContext $context, int $axis, int $amount)
	{
		parent::__construct($closure);
		$this->parents = iterator_to_array($selection->asShapeConstructors($closure, $context));
		$this->selection = $selection;
		$this->axis = $axis;
		$this->amount = $amount;
	}

	public function getBlockCount(): int
	{
		return array_sum(array_map(static fn(ShapeConstructor $constructor): int => $constructor->getBlockCount(), $this->parents)) * $this->amount;
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$size = (int) VectorUtils::getVectorAxis($this->selection->getSize(), $this->axis);
		$source = (int) VectorUtils::getVectorAxis($this->selection->getPos1(), $this->axis);
		$offsetMin = (int) floor((VectorUtils::getVectorAxis($min, $this->axis) - $source) / $size);
		$offsetMax = (int) floor((VectorUtils::getVectorAxis($max, $this->axis) - $source) / $size);
		if ($this->amount < 0) {
			[$offsetMin, $offsetMax] = [-$offsetMax, -$offsetMin];
		}
		$offsetMin = max($offsetMin, 1);
		$offsetMax = min($offsetMax, abs($this->amount));
		for ($i = $offsetMin; $i <= $offsetMax; $i++) {
			foreach ($this->parents as $parent) {
				$parent->offset(Vector3::zero()->getSide($this->axis << 1 | ($this->amount > 0 ? 1 : 0), $i * $size))->moveTo($chunk);
			}
		}
	}

	public function offset(Vector3 $offset): ShapeConstructor
	{
		throw new BadMethodCallException("Stacked cubes can't be offset");
	}
}