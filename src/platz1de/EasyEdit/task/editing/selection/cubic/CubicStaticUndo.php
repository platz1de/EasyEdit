<?php

namespace platz1de\EasyEdit\task\editing\selection\cubic;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;

trait CubicStaticUndo
{
	abstract public function getWorld(): string;

	abstract public function getTotalSelection(): Selection;

	/**
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection("undo", $this->getWorld(), $this->getTotalSelection()->getCubicStart(), $this->getTotalSelection()->getCubicEnd());
	}
}