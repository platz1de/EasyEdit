<?php

namespace platz1de\EasyEdit\task\editing\pathfinding;

use SplHeap;

class NodeHeap extends SplHeap
{
	/**
	 * @param Node $value1
	 * @param Node $value2
	 * @return int
	 */
	public function compare($value1, $value2): int
	{
		return (int) ($value2->getF() - $value1->getF());
	}
}