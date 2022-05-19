<?php

namespace platz1de\EasyEdit\task\editing\pathfinding;

use SplHeap;

/**
 * @phpstan-extends SplHeap<Node>
 */
class NodeHeap extends SplHeap
{
	/**
	 * @param Node $value1
	 * @param Node $value2
	 * @return int
	 * @phpstan-ignore-next-line we really don't want to validate classes thousands of times just to satisfy phpstan
	 */
	public function compare($value1, $value2): int
	{
		return (int) ($value2->getF() - $value1->getF());
	}
}