<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class Cube extends Selection implements Patterned
{
	use CubicChunkLoader;

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			yield new CubicConstructor($closure, $this->getPos1(), $this->getPos2());
			return;
		}

		if ($context->includesFilling()) {
			//This can also make the selection larger (1x1 -> -3x-3), so we are not allowed to actually check for the smaller/larger position
			yield new CubicConstructor($closure, $this->getCubicStart()->add(1, 1, 1), $this->getCubicEnd()->subtract(1, 1, 1));
		}

		if ($context->includesAllSides()) {
			yield from CubicConstructor::forSides($this->getPos1(), $this->getPos2(), Facing::ALL, $context->getSideThickness(), $closure);
		} elseif ($context->includesWalls()) {
			yield from CubicConstructor::forSides($this->getPos1(), $this->getPos2(), Facing::HORIZONTAL, $context->getSideThickness(), $closure);
		}

		if ($context->includesCenter()) {
			yield new CubicConstructor($closure, $this->getPos1()->addVector($this->getPos2())->divide(2)->floor(), $this->getPos1()->addVector($this->getPos2())->divide(2)->ceil());
		}
	}
}