<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class Cube extends Selection implements Patterned
{
	use CubicChunkLoader;

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			CubicConstructor::betweenPoints(Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max), $closure);
			return;
		}

		if ($context->includesFilling()) {
			//This can also make the selection larger (1x1 -> -3x-3), so we are not allowed to actually check for the smaller/larger position
			CubicConstructor::betweenPoints(Vector3::maxComponents($this->getCubicStart()->add(1, 1, 1), $this->getCubicStart(), $min), Vector3::minComponents($this->getCubicEnd()->subtract(1, 1, 1), $this->getCubicEnd(), $max), $closure);
		}

		if ($context->includesAllSides()) {
			CubicConstructor::onSides(Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max), Facing::ALL, $context->getSideThickness(), $closure);
		} elseif ($context->includesWalls()) {
			CubicConstructor::onSides(Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max), Facing::HORIZONTAL, $context->getSideThickness(), $closure);
		}

		if ($context->includesCenter()) {
			CubicConstructor::betweenPoints(Vector3::maxComponents($this->getPos1()->addVector($this->getPos2())->divide(2)->floor(), $this->getPos1(), $min), Vector3::minComponents($this->getPos1()->addVector($this->getPos2())->divide(2)->ceil(), $this->getPos2(), $max), $closure);
		}
	}
}