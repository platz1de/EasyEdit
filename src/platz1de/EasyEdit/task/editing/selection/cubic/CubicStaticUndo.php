<?php

namespace platz1de\EasyEdit\task\editing\selection\cubic;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\session\SessionIdentifier;

trait CubicStaticUndo
{
	abstract public function getWorld(): string;

	abstract public function getTotalSelection(): Selection;

	/**
	 * @param SessionIdentifier $executor
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(SessionIdentifier $executor): BlockListSelection
	{
		return new StaticBlockListSelection($executor->getName(), $this->getWorld(), $this->getTotalSelection()->getCubicStart(), $this->getTotalSelection()->getCubicEnd());
	}
}